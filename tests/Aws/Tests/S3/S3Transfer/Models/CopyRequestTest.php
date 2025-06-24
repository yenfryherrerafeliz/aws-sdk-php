<?php
namespace Aws\Tests\S3\S3Transfer\Models;

use Aws\S3\S3Transfer\Models\CopyRequest;
use Aws\S3\S3Transfer\Progress\TransferListener;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CopyRequestTest extends TestCase
{
    private static array $validSource = ['Bucket' => 'src-bucket', 'Key' => 'src-key'];
    private static array $validArgs   = ['Bucket' => 'dst-bucket', 'Key' => 'dst-key'];

    public function testGetSourceAndDestinationArgsAreExposedByGetters(): void
    {
        $listeners = [$this->createMock(TransferListener::class)];
        $tracker = $this->createMock(TransferListener::class);
        $config = ['concurrency' => 2];

        $req = new CopyRequest(
            self::$validSource,
            self::$validArgs,
            $config,
            $listeners,
            $tracker
        );

        $this->assertSame(self::$validSource, $req->getSource());
        $this->assertSame(self::$validArgs, $req->getCopyRequestArgs());
        $this->assertSame($listeners, $req->getListeners());
        $this->assertSame($tracker, $req->getProgressTracker());

        $returnedConfig = $req->getConfig();
        $this->assertArrayHasKey('concurrency', $returnedConfig);
        $this->assertEquals(2,                  $returnedConfig['concurrency']);
    }

    public function testValidateSourceSucceeds(): void
    {
        $req = CopyRequest::fromLegacyArgs(
            self::$validSource,
            self::$validArgs,
            [],
            [],
            null
        );

        $req->validateSourceAndDest();
        $req->validateRequiredParameters();
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider sourceValidationProvider
     */
    public function testValidateSourceThrows($src, $args, string $expectedMessage): void
    {
        $req = CopyRequest::fromLegacyArgs($src, $args);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $req->validateSourceAndDest();
    }

    public function sourceValidationProvider(): array
    {
        return [
            [[], [], 'Both `Bucket` and `Key` must be provided in the source array.'],
            [['Bucket' => 'a'], ['Bucket' => 'b','Key' => 'c'], 'Both `Bucket` and `Key` must be provided in the source array.'],
            [['Bucket' => 'a','Key' => 'b'], ['Bucket' => 'c'], 'Both `Bucket` and `Key` must be provided in the copy request arguments.'],
            [['Bucket' => 'a','Key' => 'b'], ['Key' => 'c'], 'Both `Bucket` and `Key` must be provided in the copy request arguments.'],
            [['Bucket' => 'x','Key' => 'y'], ['Bucket' => 'x','Key' => 'y'], 'Source and destination cannot be the same object.'],
        ];
    }

    /**
     * @dataProvider requiredParamsProvider
     */
    public function testValidateRequiredParameters($args, string $expected): void
    {
        $req = CopyRequest::fromLegacyArgs(self::$validSource, $args);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expected);
        $req->validateRequiredParameters();
    }

    public function requiredParamsProvider(): array
    {
        return [
            [['Key' => 'foo'], "The 'Bucket' parameter must be provided as part of the copy request arguments."],
            [['Bucket' => 'bar'], "The 'Key' parameter must be provided as part of the copy request arguments."],
        ];
    }
}
