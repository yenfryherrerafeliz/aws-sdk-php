<?php

namespace Aws\Test\S3\S3Transfer;

use Aws\Command;
use Aws\Result;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use Aws\S3\S3Transfer\Models\UploadResponse;
use Aws\S3\S3Transfer\MultipartUploader;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class MultipartUploaderTest extends TestCase
{

    /**
     * @param StreamInterface $stream
     * @param array $config
     * @param array $expected
     * @return void
     *
     * @dataProvider multipartUploadProvider
     *
     */
    public function testMultipartUpload(
        StreamInterface $stream,
        array $config,
        array $expected
    ): void
    {
        $s3Client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $s3Client->method('executeAsync')
            -> willReturnCallback(function ($command)
           {
                if ($command->getName() === 'CreateMultipartUpload') {
                    return Create::promiseFor(new Result([
                        'UploadId' => 'FooUploadId'
                    ]));
                } elseif ($command->getName() === 'UploadPart') {
                    return Create::promiseFor(new Result([
                        'ETag' => 'FooETag'
                    ]));
                }

               return Create::promiseFor(new Result([]));
            });
        $s3Client->method('getCommand')
            -> willReturnCallback(function ($commandName, $args) {
                return new Command($commandName, $args);
            });
        $requestArgs = [
            'Key' => 'FooKey',
            'Bucket' => 'FooBucket',
        ];
        $multipartUploader = new MultipartUploader(
            $s3Client,
            $requestArgs,
            $config + [
                'concurrency' => 3,
            ],
            $stream
        );
        /** @var UploadResponse $response */
        $response = $multipartUploader->promise()->wait();
        $snapshot = $multipartUploader->getCurrentSnapshot();

        $this->assertInstanceOf(UploadResponse::class, $response);
        $this->assertCount($expected['parts'], $multipartUploader->getParts());
        $this->assertEquals($expected['bytesUploaded'], $snapshot->getTransferredBytes());
        $this->assertEquals($expected['bytesUploaded'], $snapshot->getTotalBytes());
    }

    /**
     * @return array[]
     */
    public function multipartUploadProvider(): array {
        return [
            '5_parts_upload' => [
                'stream' => Utils::streamFor(
                    str_repeat('*', 10240000 * 5),
                ),
                'config' => [
                    'part_size' => 10240000
                ],
                'expected' => [
                    'succeed' => true,
                    'parts' => 5,
                    'bytesUploaded' => 10240000 * 5,
                ]
            ],
            '100_parts_upload' => [
                'stream' => Utils::streamFor(
                    str_repeat('*', 10240000 * 100),
                ),
                'config' => [
                    'part_size' => 10240000
                ],
                'expected' => [
                    'succeed' => true,
                    'parts' => 100,
                    'bytesUploaded' => 10240000 * 100,
                ]
            ],
            '5_parts_no_seekable_stream' => [
                'stream' => new NoSeekStream(
                    Utils::streamFor(
                        str_repeat('*', 10240000 * 5)
                    )
                ),
                'config' => [
                    'part_size' => 10240000
                ],
                'expected' => [
                    'succeed' => true,
                    'parts' => 5,
                    'bytesUploaded' => 10240000 * 5,
                ]
            ],
            '100_parts_no_seekable_stream' => [
                'stream' => new NoSeekStream(
                    Utils::streamFor(
                        str_repeat('*', 10240000 * 100)
                    )
                ),
                'config' => [
                    'part_size' => 10240000
                ],
                'expected' => [
                    'succeed' => true,
                    'parts' => 100,
                    'bytesUploaded' => 10240000 * 100,
                ]
            ]
        ];
    }

    /**
     * @return S3ClientInterface
     */
    private function multipartUploadS3Client(): S3ClientInterface
    {
        return new S3Client([
            'region' => 'us-east-2',
            'http_handler' => function (RequestInterface $request) {
                $uri = $request->getUri();
                // Create multipart upload
                if ($uri->getQuery() === 'uploads') {
                    $body = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<InitiateMultipartUploadResult>
    <Bucket>Foo</Bucket>
    <Key>Test file</Key>
    <UploadId>FooUploadId</UploadId>
</InitiateMultipartUploadResult>
EOF;
                    return new Response(200, [], $body);
                }

                // Parts upload
                if (str_starts_with($request->getUri(), 'uploadId=') && str_contains($request->getUri(), 'partNumber=')) {
                    return new Response(200, ['ETag' => random_bytes(16)]);
                }

                // Complete multipart upload
                return new Response(200, [], null);
            }
        ]);
    }

    /**
     * @return void
     */
    public function testInvalidSourceStringThrowsException(): void
    {
        $nonExistentFile = '/path/to/nonexistent/file.txt';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "The source for this upload must be either a readable file or a valid stream."
        );
        new MultipartUploader(
            $this->multipartUploadS3Client(),
            ['Bucket' => 'test-bucket', 'Key' => 'test-key'],
            [],
            $nonExistentFile
        );
    }

    /**
     * @return void
     */
    public function testInvalidSourceTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "The source for this upload must be either a readable file or a valid stream."
        );
        new MultipartUploader(
            $this->multipartUploadS3Client(),
            ['Bucket' => 'test-bucket', 'Key' => 'test-key'],
            [],
            12345
        );
    }
}