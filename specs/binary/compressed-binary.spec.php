<?php
use Peridot\WebDriverManager\Binary\CompressedBinary;
use Peridot\WebDriverManager\OS\System;
use Prophecy\Argument;

describe('CompressedBinary', function () {
    beforeEach(function () {
        $this->request = $this->getProphet()->prophesize('Peridot\WebDriverManager\Binary\Request\BinaryRequestInterface');
        $this->decompressor = $this->getProphet()->prophesize('Peridot\WebDriverManager\Binary\Decompression\BinaryDecompressorInterface');
        $this->binary = new TestCompressedBinary($this->request->reveal(), $this->decompressor->reveal(), new System());
        $this->request->request($this->binary->getUrl())->willReturn('string');

        $fixtures = glob(__DIR__ . "/test-*");
        foreach ($fixtures as $fixture) {
            unlink($fixture);
        }
    });

    afterEach(function () {
        $this->getProphet()->checkPredictions();
    });

    describe('->save()', function () {
        beforeEach(function () {
            $this->binary->fetch();
        });

        it('write the zip contents to an output file', function () {
            $this->binary->save(__DIR__);
            $this->decompressor->extract(Argument::containingString($this->binary->getOutputFileName()), __DIR__)->shouldBeCalled();
        });

        it('should return true if decompression succeeds', function () {
            $this->decompressor->extract(Argument::containingString($this->binary->getOutputFileName()), __DIR__)->willReturn(true);
            $result = $this->binary->save(__DIR__);
            expect($result)->to->be->true;
        });

        it('should return false if decompression fails', function () {
            $this->decompressor->extract(Argument::containingString($this->binary->getOutputFileName()), __DIR__)->willReturn(false);
            $result = $this->binary->save(__DIR__);
            expect($result)->to->be->false;
        });

        context('when the current version is already installed', function () {
            beforeEach(function () {
                file_put_contents(__DIR__ . '/' . $this->binary->getOutputFileName(), 'zipzipzip');
            });

            it('should return true without unzipping', function () {
                $this->decompressor->extract()->shouldNotBeCalled();
                $result = $this->binary->save(__DIR__);
                expect($result)->to->be->true;
            });
        });
    });

    describe('->fetchAndSave()', function () {
        it('should fetch and save contents', function () {
            $this->decompressor->extract(Argument::containingString($this->binary->getOutputFileName()), __DIR__)->willReturn(true);
            $result = $this->binary->fetchAndSave(__DIR__);
            expect($result)->to->be->true;
        });

        it('should return true if already installed and up to date', function () {
            $this->decompressor->extract(Argument::containingString($this->binary->getOutputFileName()), __DIR__)->willReturn(true);
            $this->binary->fetchAndSave(__DIR__);
            $decompressor = $this->getProphet()->prophesize('Peridot\WebDriverManager\Binary\Decompression\BinaryDecompressorInterface');
            $binary = new TestCompressedBinary($this->request->reveal(), $decompressor->reveal(), new System());
            expect($binary->fetchAndSave(__DIR__))->to->be->true;
        });
    });
});

class TestCompressedBinary extends CompressedBinary
{
    public function getName()
    {
        return 'test-compressed-binary';
    }

    public function getFileName()
    {
        return 'test-compressed-binary.zip';
    }

    public function getUrl()
    {
        return 'http://url.com';
    }

    public function getOutputFileName()
    {
        return 'test-compressed-output.zip';
    }
}
