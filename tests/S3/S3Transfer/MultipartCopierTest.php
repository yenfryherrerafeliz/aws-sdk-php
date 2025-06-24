<?php
namespace Aws\Test\S3\S3Transfer;

use Aws\Result;
use Aws\Command;
use Aws\Exception\AwsException;
use Aws\S3\S3Transfer\AbstractMultipartUploader;
use Aws\S3\S3Transfer\Models\CopyResult;
use Aws\S3\S3Transfer\MultipartCopier;
use Aws\S3\S3Transfer\Progress\TransferListener;
use Aws\S3\S3Transfer\Progress\TransferListenerNotifier;
use Aws\Test\UsesServiceTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MultipartCopierTest extends TestCase
{
    use UsesServiceTrait;

    private $client;

    // Initialize the mock-enabled S3 client
    protected function setUp(): void
    {
        $this->client = $this->getTestClient('s3');
    }

    /**
     * Create a mocked notifier expecting each provided method to be called count times.
     * The array keys are method names, values are expected call counts.
     */
    private function createNotifier(array $methodsAndCounts): TransferListenerNotifier
    {
        $notifier = $this->getMockBuilder(TransferListenerNotifier::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_keys($methodsAndCounts))
            ->getMock();

        foreach ($methodsAndCounts as $method => $count) {
            $expect = $count === 1 ? $this->once() : $this->exactly($count);
            $notifier->expects($expect)
                ->method($method)
                ->with($this->arrayHasKey(TransferListener::PROGRESS_SNAPSHOT_KEY));
        }

        return $notifier;
    }

    /**
     * Stub S3 responses, optionally expect an exception, and run the copier.
     * @param array                $responses          List of Result or AwsException to queue
     * @param array                $config             Config for part_size/concurrency
     * @param array                $source             ['Bucket'=>..., 'Key'=>...]
     * @param TransferListenerNotifier $notifier        Progress notifier spy
     * @param string|null          $exceptionClass     Expected exception class, if any
     * @param string|null          $exceptionMessage   Expected exception message fragment
     */
    private function runCopier(
        array $responses,
        array $config,
        array $source,
        TransferListenerNotifier $notifier,
        string $exceptionClass = null,
        string $exceptionMessage = null
    ): void
    {
        $this->addMockResults($this->client, $responses);

        if ($exceptionClass !== null) {
            $this->expectException($exceptionClass);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $copier = new MultipartCopier(
            $this->client,
            ['Bucket' => 'dest', 'Key' => 'key'],
            $config,
            $source,
            null,
            [],
            null,
            $notifier
        );

        $copier->copy()->wait();
    }

    /**
     * @return void
     */
    public function testThrowsWhenTooManyParts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total parts cannot exceed');

        $oversizedObject = (AbstractMultipartUploader::PART_MAX_NUM + 1)
            * AbstractMultipartUploader::PART_MIN_SIZE;

        (new MultipartCopier(
            $this->client,
            ['Bucket' => 'dest', 'Key' => 'key'],
            [
                'part_size' => AbstractMultipartUploader::PART_MIN_SIZE,
                'concurrency' => 1,
                'object_size' => $oversizedObject,
            ],
            ['Bucket' => 'src', 'Key' => 'key']
        ))->copy()->wait();
    }

    /**
     * @return void
     */
    public function testUsesDefaultPartSizeWhenNotPassed(): void
    {
        $this->addMockResults($this->client, [
            new Result(['ContentLength' => 10 * 1024 * 1024]),
            new Result(['UploadId' => 'upload-id']),
            new Result(['CopyPartResult'=> ['ETag' => 'a']]),
            new Result(['CopyPartResult'=> ['ETag' => 'b']]),
            new Result(['Location' => 'u','Key' => 'k','Bucket' => 'b']),
        ]);

        $mockCopier = $this->getMockBuilder(MultipartCopier::class)
            ->setConstructorArgs([
                $this->client,
                ['Bucket' => 'b', 'Key' => 'k'],
                ['concurrency' => 1],
                ['Bucket' => 's', 'Key' => 'o'],
            ])
            ->onlyMethods(['partCompleted'])
            ->getMock();

        $mockCopier->expects($this->exactly(2))
            ->method('partCompleted')
            ->with(
                MultipartCopier::PART_MIN_SIZE,
                $this->arrayHasKey('CopySourceRange')
            );

        $mockCopier->copy()->wait();
    }

    /**
     * @dataProvider invalidConstructorProvider
     */
    public function testConstructorThrowsOnInvalidInputs(
        array $dest,
        array $config,
        mixed $source,
        string $expectedException
    ): void
    {
        $this->expectException($expectedException);
        new MultipartCopier(
            $this->client,
            $dest,
            $config,
            $source
        );
    }

    public function invalidConstructorProvider(): array
    {
        return [
            'Invalid source type (string)' => [
                'dest' => ['Bucket' => 'dest-bucket', 'Key' => 'dest-key'],
                'config' => ['part_size' => AbstractMultipartUploader::PART_MIN_SIZE, 'concurrency' => 1],
                'source' => 'not-an-array',
                'expectedException' => \TypeError::class,
            ],
            'Missing source Key' => [
                'dest' => ['Bucket' => 'dest', 'Key' => 'dest-key'],
                'config' => ['part_size' => AbstractMultipartUploader::PART_MIN_SIZE, 'concurrency' => 1],
                'source' => ['Bucket' => 'src', 'Key' => ''],
                'expectedException' => \InvalidArgumentException::class,
            ],
            'Same source and destination' => [
                'dest' => ['Bucket' => 'bucket', 'Key' => 'key'],
                'config' => ['part_size' => AbstractMultipartUploader::PART_MIN_SIZE, 'concurrency' => 1],
                'source' => ['Bucket' => 'bucket', 'Key' => 'key'],
                'expectedException' => \InvalidArgumentException::class,
            ],
            'Invalid part size' => [
                'dest' => ['Bucket' => 'dest', 'Key' => 'dest-key'],
                'config' => ['part_size' => 1 * 1024 * 1024, 'concurrency' => 1],
                'source' => ['Bucket' => 'src', 'Key' => 'key'],
                'expectedException' => \InvalidArgumentException::class,
            ],
        ];
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testGetSourcePathIncludesVersionId(): void
    {
        $source = ['Bucket' => 'bucket','Key' => 'key','VersionId' => 'v1'];
        $this->addMockResults($this->client, [ new Result(['ContentLength' => 1]) ]);

        $copier = new MultipartCopier(
            $this->client,
            ['Bucket' => 'dest', 'Key' => 'dest-key'],
            ['part_size' => AbstractMultipartUploader::PART_MIN_SIZE, 'concurrency' => 1],
            $source
        );

        $method = (new \ReflectionClass($copier))->getMethod('getSourcePath');
        $path = $method->invoke($copier, $source);
        $this->assertStringContainsString('?versionId=v1', $path);
    }

    /**
     * @return void
     */
    public function testMultipartCopyWorkflowIsSuccessful(): void
    {
        $url = 'http://dest.s3.amazonaws.com/dest-key';
        $this->addMockResults($this->client, [
            new Result(['ContentLength' => 10 * 1024 * 1024]),
            new Result(['UploadId' => 'upload-id']),
            new Result(['CopyPartResult'=> ['ETag' => 'etag1']]),
            new Result(['CopyPartResult'=> ['ETag' => 'etag2']]),
            new Result(['Location' => $url,'Key' => 'dest-key','Bucket' => 'dest']),
        ]);

        $copier = new MultipartCopier(
            $this->client,
            ['Bucket' => 'dest','Key' => 'dest-key'],
            ['part_size' => 5 * 1024 * 1024,'concurrency' => 1],
            ['Bucket' => 'src','Key' => 'key']
        );

        $result   = $copier->copy()->wait();

        $this->assertInstanceOf(CopyResult::class, $result);
        $parts = $copier->getParts();
        $this->assertCount(2, $parts);
        $this->assertSame('etag1', $parts[0]['ETag']);
        $this->assertSame('etag2', $parts[1]['ETag']);
        $this->assertSame($url, $result['ObjectURL']);
    }

    /**
     * @return void
     */
    public function testPartCompletedCountIsCorrect(): void
    {
        $url = 'http://dest.s3.amazonaws.com/dest-key';
        $this->addMockResults($this->client, [
            new Result(['ContentLength' => 10 * 1024 * 1024]),
            new Result(['UploadId' => 'upload-id']),
            new Result(['CopyPartResult'=> ['ETag' => 'etag1']]),
            new Result(['CopyPartResult'=> ['ETag' => 'etag2']]),
            new Result(['Location' => $url,'Key'=>'dest-key','Bucket'=>'dest']),
        ]);

        $mockCopier = $this->getMockBuilder(MultipartCopier::class)
            ->setConstructorArgs([
                $this->client,
                ['Bucket'=>'dest','Key'=>'dest-key'],
                ['part_size'=>5 * 1024 * 1024,'concurrency'=>1],
                ['Bucket'=>'src','Key'=>'key'],
            ])
            ->onlyMethods(['partCompleted'])
            ->getMock();

        $mockCopier->expects($this->exactly(2))
            ->method('partCompleted')
            ->with(
                5 * 1024 * 1024,
                $this->arrayHasKey('CopySourceRange')
            );

        $mockCopier->copy()->wait();
    }

    /**
     * @return void
     */
    public function testAbortMultipartUploadOnFailure(): void
    {
        $this->addMockResults($this->client, [
            new Result(['ContentLength' => 10 * 1024 * 1024]),
            new Result(['UploadId' => 'upload-id']),
            new Result(['CopyPartResult'=> ['ETag' => 'etag1']]),
            new AwsException('Part-copy exploded!', new Command('UploadPartCopy', [])),
        ]);

        $abortCalls = 0;
        $mockCopier = $this->getMockBuilder(MultipartCopier::class)
            ->setConstructorArgs([
                $this->client,
                ['Bucket'=>'dest','Key'=>'dest-key'],
                ['part_size'=>5 * 1024 * 1024,'concurrency'=>1],
                ['Bucket'=>'src','Key'=>'key'],
            ])
            ->onlyMethods(['abortMultipartOperation'])
            ->getMock();

        $mockCopier->method('abortMultipartOperation')
            ->willReturnCallback(function () use (&$abortCalls) {
                $abortCalls++;
                return \GuzzleHttp\Promise\Create::promiseFor(null);
            });

        try {
            $mockCopier->copy()->wait();
            $this->fail('Expected AwsException was not thrown');
        } catch (AwsException $e) {
            $this->assertStringContainsString('Part-copy exploded!', $e->getMessage());
            $this->assertSame(1, $abortCalls);
        }
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testGetSourceSizeReturnsContentLength(): void
    {
        $this->addMockResults($this->client, [ new Result(['ContentLength'=>200]) ]);

        $copier = new MultipartCopier(
            $this->client,
            ['Bucket'=>'dest','Key'=>'dest-key'],
            ['part_size'=> 5 * 1024 * 1024,'concurrency'=>1],
            ['Bucket'=>'src','Key'=>'src-key'],
        );

        $m = new \ReflectionMethod(MultipartCopier::class, 'getSourceSize');
        $actual = $m->invoke($copier);
        $this->assertSame(200, $actual);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testGetSourceSizeThrowsRuntimeExceptionOnHeadObjectError(): void
    {
        $this->addMockResults($this->client, [
            new AwsException('Oops! S3 is down', new Command('HeadObject', []))
        ]);

        $copier = new MultipartCopier(
            $this->client,
            ['Bucket'=>'dest','Key'=>'dest-key'],
            ['part_size'=>5 * 1024 * 1024,'concurrency'=>1],
            ['Bucket'=>'src','Key'=>'src-key'],
        );

        $m = new \ReflectionMethod(MultipartCopier::class, 'getSourceSize');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to get source object size: Oops! S3 is down');

        $m->invoke($copier);
    }

    /**
     * @return void
     */
    public function testProgressNotificationsOnSuccessfulCopy(): void
    {
        $notifier = $this->createNotifier([
            'transferInitiated' => 1,
            'bytesTransferred' => 2,
            'transferComplete' => 1,
        ]);

        $responses = [
            new Result(['ContentLength' => 10 * 1024 * 1024]),
            new Result(['UploadId' => 'upload-id']),
            new Result(['CopyPartResult'=> ['ETag' => 'a']]),
            new Result(['CopyPartResult'=> ['ETag' => 'b']]),
            new Result(['Location' => 'u', 'Key' => 'k', 'Bucket' => 'b']),
        ];

        $this->runCopier(
            $responses,
            ['part_size' => 5 * 1024 * 1024, 'concurrency' => 1],
            ['Bucket' => 'src', 'Key' => 'key'],
            $notifier
        );
    }

    /**
     * @return void
     */
    public function testProgressNotificationsOnFailureSingleCopy(): void
    {
        $notifier = $this->createNotifier([
            'transferInitiated' => 1,
            'transferFail' => 1,
        ]);

        $responses = [
            new Result(['ContentLength' => 3 * 1024 * 1024]),
            new AwsException('Copy failed', new Command('CopyObject', [])),
        ];

        $this->runCopier(
            $responses,
            ['concurrency' => 1],
            ['Bucket' => 'src', 'Key' => 'key'],
            $notifier,
            \Exception::class,
            'Copy failed'
        );
    }

    /**
     * @return void
     */
    public function testTransferListenerNotifierWithEmptyListeners(): void
    {
        $listenerNotifier = new TransferListenerNotifier([]);

        $this->addMockResults($this->client, [
            new Result(['ContentLength' => 5 * 1024 * 1024]),
            new Result(['UploadId' => 'TestUploadId']),
            new Result(['CopyPartResult' => ['ETag' => 'TestETag']]),
            new Result(['Location' => 'url', 'Key' => 'dest-key', 'Bucket' => 'dest']),
        ]);

        $copier = new MultipartCopier(
            $this->client,
            ['Bucket' => 'dest', 'Key' => 'dest-key'],
            ['part_size' => 5 * 1024 * 1024, 'concurrency' => 1],
            ['Bucket' => 'src', 'Key' => 'src-key'],
            null,
            [],
            null,
            $listenerNotifier
        );

        $response = $copier->copy()->wait();
        $this->assertInstanceOf(CopyResult::class, $response);
    }

    /**
     * @return void
     */
    public function testMultipartOperationsAreCalled(): void
    {
        $operationsCalled = [
            'CreateMultipartUpload' => false,
            'UploadPartCopy' => false,
            'CompleteMultipartUpload' => false,
        ];

        $responseQueue = [
            new Result(['ContentLength' => 10 * 1024 * 1024]),
            new Result(['UploadId' => 'upload-id']),
            new Result(['CopyPartResult'=> ['ETag' => 'etag1']]),
            new Result(['CopyPartResult'=> ['ETag' => 'etag2']]),
            new Result([
                'Location' => 'http://dest.s3.amazonaws.com/dest-key',
                'Key'      => 'dest-key',
                'Bucket'   => 'dest',
            ]),
        ];

        $s3Client = $this->getMockBuilder(\Aws\S3\S3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['executeAsync', 'getCommand'])
            ->getMock();

        $s3Client->method('executeAsync')
            ->willReturnCallback(function ($cmd) use (&$operationsCalled, &$responseQueue) {
                $operationsCalled[$cmd->getName()] = true;
                $next = array_shift($responseQueue);
                return \GuzzleHttp\Promise\Create::promiseFor($next);
            });

        $s3Client->method('getCommand')
            ->willReturnCallback(function ($name, $args) {
                return new Command($name, $args);
            });

        $copier = new MultipartCopier(
            $s3Client,
            ['Bucket' => 'dest', 'Key' => 'dest-key'],
            ['concurrency' => 1],
            ['Bucket' => 'src',  'Key' => 'src-key']
        );

        $copier->copy()->wait();

        foreach ($operationsCalled as $op => $wasCalled) {
            $this->assertTrue(
                $wasCalled,
                "Expected operation {$op} to be called"
            );
        }
    }

    /**
     * @param int $partSize
     * @param bool $expectError
     *
     * @dataProvider validatePartSizeProvider
     *
     * @return void
     */
    public function testValidatePartSize(
        int $partSize,
        bool $expectError
    ): void
    {
        if ($expectError) {
            $this->expectException(\InvalidArgumentException::class);

        } else {
            $this->assertTrue(true);
        }

        new MultipartCopier(
            $this->client,
            ['Bucket' => 'test-bucket', 'Key' => 'test-key'],
            [
                'part_size' => $partSize,
            ],
            ['Bucket' => 'src', 'Key' => 'src-key']
        );
    }

    /**
     * @return array
     */
    public function validatePartSizeProvider(): array {
        return [
            'part_size_over_max' => [
                'part_size' => AbstractMultipartUploader::PART_MAX_SIZE + 1,
                'expectError' => true,
            ],
            'part_size_under_min' => [
                'part_size' => AbstractMultipartUploader::PART_MIN_SIZE - 1,
                'expectError' => true,
            ],
            'part_size_between_valid_range_1' => [
                'part_size' => AbstractMultipartUploader::PART_MAX_SIZE - 1,
                'expectError' => false,
            ],
            'part_size_between_valid_range_2' => [
                'part_size' => AbstractMultipartUploader::PART_MIN_SIZE + 1,
                'expectError' => false,
            ]
        ];
    }

    /**
     * @return void
     */
    public function testTransferListenerNotifierNotifiesListenersOnSuccess(): void
    {
        $listener1 = $this->getMockBuilder(TransferListener::class)->getMock();
        $listener2 = $this->getMockBuilder(TransferListener::class)->getMock();
        $listener3 = $this->getMockBuilder(TransferListener::class)->getMock();

        $listener1->expects($this->once())->method('transferInitiated');
        $listener1->expects($this->atLeastOnce())->method('bytesTransferred');
        $listener1->expects($this->once())->method('transferComplete');

        $listener2->expects($this->once())->method('transferInitiated');
        $listener2->expects($this->atLeastOnce())->method('bytesTransferred');
        $listener2->expects($this->once())->method('transferComplete');

        $listener3->expects($this->once())->method('transferInitiated');
        $listener3->expects($this->atLeastOnce())->method('bytesTransferred');
        $listener3->expects($this->once())->method('transferComplete');

        $listenerNotifier = new TransferListenerNotifier([$listener1, $listener2, $listener3]);

        $this->addMockResults($this->client, [
            new Result(['ContentLength' => 5 * 1024 * 1024]),
            new Result(['UploadId' => 'TestUploadId']),
            new Result(['CopyPartResult' => ['ETag' => 'TestETag']]),
            new Result(['Location' => 'url', 'Key' => 'dest-key', 'Bucket' => 'dest']),
        ]);

        $copier = new MultipartCopier(
            $this->client,
            ['Bucket' => 'dest', 'Key' => 'dest-key'],
            ['part_size' => 5 * 1024 * 1024, 'concurrency' => 1],
            ['Bucket' => 'src', 'Key' => 'src-key'],
            null,
            [],
            null,
            $listenerNotifier
        );

        $response = $copier->copy()->wait();
        $this->assertInstanceOf(CopyResult::class, $response);
    }

    /**
     * @return void
     */
    public function testTransferListenerNotifierNotifiesListenersOnFailure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Copy failed');

        $listener1 = $this->getMockBuilder(TransferListener::class)->getMock();
        $listener2 = $this->getMockBuilder(TransferListener::class)->getMock();

        $listener1->expects($this->once())->method('transferInitiated');
        $listener1->expects($this->once())->method('transferFail');
        $listener2->expects($this->once())->method('transferInitiated');
        $listener2->expects($this->once())->method('transferFail');

        $listenerNotifier = new TransferListenerNotifier([$listener1, $listener2]);

        $this->addMockResults($this->client, [
            new Result(['ContentLength' => 5 * 1024 * 1024]),
            new Result(['UploadId' => 'TestUploadId']),
            new AwsException('Copy failed', new Command('UploadPartCopy', [])),
            new Result([]),
        ]);

        $copier = new MultipartCopier(
            $this->client,
            ['Bucket' => 'dest', 'Key' => 'dest-key'],
            ['part_size' => 5 * 1024 * 1024, 'concurrency' => 1],
            ['Bucket' => 'src',  'Key' => 'src-key'],
            null,
            [],
            null,
            $listenerNotifier
        );

        $copier->copy()->wait();
    }

}
