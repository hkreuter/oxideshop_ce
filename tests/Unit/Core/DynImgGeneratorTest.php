<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use oxDynImgGenerator;
use OxidEsales\EshopCommunity\Core\DynamicImageGenerator;
use OxidEsales\EshopCommunity\Core\Exception\StandardException;

final class DynImgGeneratorTest extends \OxidTestCase
{
    public function testGetInstance(): void
    {
        $this->assertInstanceOf('oxDynImgGenerator', \OxidEsales\EshopCommunity\Core\DynamicImageGenerator::getInstance());
    }

    public function testGetImageUri(): void
    {
        $oGen = oxNew('oxDynImgGenerator');
        $this->assertEquals(isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "", $oGen->UNITgetImageUri());
    }

    public function testGetImageUriWithDoubleSlash(): void
    {
        $sRequestedImageUri = "/out/pictures//generated/path/to/test.jpg";
        $sExpectedUri = "out/pictures/generated/path/to/test.jpg";

        $sRequestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
        $_SERVER['REQUEST_URI'] = $sRequestedImageUri;

        $oGen = new oxDynImgGenerator();
        $this->assertEquals($sExpectedUri, $oGen->UNITgetImageUri());

        $_SERVER['REQUEST_URI'] = $sRequestUri;
    }

    public function testGetImageName(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageUri"));
        $oGen
            ->method('_getImageUri')
            ->willReturnOnConsecutiveCalls('/test1/test2/test3/test4/test.jpg', '');

        $this->assertEquals("test.jpg", $oGen->UNITgetImageName());
        $this->assertEquals("", $oGen->UNITgetImageName());
    }

    public function testGetImageMasterPath(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageUri"));
        $oGen
            ->method('_getImageUri')
            ->willReturnOnConsecutiveCalls('', '/test1/test2/test3/test4/test.jpg');

        $this->assertFalse($oGen->UNITgetImageMasterPath());
        $this->assertEquals("/master/test2/test3/", $oGen->UNITgetImageMasterPath());
    }

    public function testGetImageInfo(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageUri"));
        $oGen
            ->method('_getImageUri')
            ->willReturnOnConsecutiveCalls(
                '',
                '/test1/test2/test3/test4/test.jpg',
                '/test1/test2/test3/12_12_12/test.jpg'
            );

        $this->assertEquals([0 ,0, 0], $oGen->UNITgetImageInfo());
        $this->assertEquals(array("test4"), $oGen->UNITgetImageInfo());
        $this->assertEquals(array("12", "12", "12"), $oGen->UNITgetImageInfo());
    }

    public function testGetImageTarget(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageUri"));
        $oGen->method('_getImageUri')->will($this->returnValue("/test1/test2/test3/12_12_12/test.jpg"));

        $this->assertEquals(getShopBasePath() . "/test1/test2/test3/12_12_12/test.jpg", $oGen->UNITgetImageTarget());
    }

    public function testGetNopicImageTarget(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageUri", "_getImageName"));
        $oGen->method('_getImageUri')->will($this->returnValue("/test1/test2/test3/12_12_12/test.jpg"));
        $oGen->method('_getImageName')->will($this->returnValue("test.jpg"));

        $this->assertEquals(getShopBasePath() . "/test1/test2/test3/12_12_12/nopic.jpg", $oGen->UNITgetNopicImageTarget());
    }

    public function testIsTargetPathValid(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_isValidPath", "_createFolders"));
        $oGen->expects($this->once())->method('_isValidPath')->with($this->equalTo("/test1/test2/test3/12_12_12"))->will($this->returnValue(false));
        $oGen->expects($this->never())->method('_createFolders');

        // invalid path
        $this->assertFalse($oGen->UNITisTargetPathValid("/test1/test2/test3/12_12_12/nopic.jpg"));

        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_isValidPath", "_createFolders"));
        $oGen->expects($this->once())->method('_isValidPath')->with($this->equalTo("/test1/test2/test3/12_12_12"))->will($this->returnValue(true));
        $oGen->expects($this->once())->method('_createFolders')->with($this->equalTo("/test1/test2/test3/12_12_12"))->will($this->returnValue(true));

        // invalid path
        $this->assertTrue($oGen->UNITisTargetPathValid("/test1/test2/test3/12_12_12/nopic.jpg"));
    }

    public function testIsValidPath(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageInfo"));
        $oGen
            ->method('_getImageInfo')
            ->willReturnOnConsecutiveCalls(
                false,
                [1, 2, 3],
                [4, 5, 6],
                [7, 8, 75],
                [56, 42, 75]
            );

        // missing image info
        $this->assertFalse($oGen->UNITisValidPath("any/path"));

        // wrong path
        $this->assertFalse($oGen->UNITisValidPath("wrong/path"));

        // wrong quality param
        $this->assertFalse($oGen->UNITisValidPath("/wrong/quality/param/generated/product/icon/4_5_6"));

        // wrogn size param
        $this->assertFalse($oGen->UNITisValidPath("/wrong/size/param/generated/product/icon/7_8_75"));

        // all parameters are fine
        $this->assertTrue($oGen->UNITisValidPath("/all/params/fine/generated/product/icon/56_42_75"));
    }

    public function testGetImageType(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageName"));
        $oGen
            ->method('_getImageName')
            ->willReturnOnConsecutiveCalls(
                'test.jpg',
                'test.jpeg',
                'test.png',
                'test.gif',
                'test',
                'test.php',
                'test.exe'
            );

        $this->assertEquals("jpeg", $oGen->UNITgetImageType());
        $this->assertEquals("jpeg", $oGen->UNITgetImageType());
        $this->assertEquals("png", $oGen->UNITgetImageType());
        $this->assertEquals("gif", $oGen->UNITgetImageType());
        $this->assertFalse($oGen->UNITgetImageType());
        $this->assertFalse($oGen->UNITgetImageType());
        $this->assertFalse($oGen->UNITgetImageType());
    }

    /**
     * @dataProvider dataProviderTestGenerateImagePickGenerationMethodFromFileExtension
     *
     * @param $sourceFilePath
     * @param $targetFilePath
     * @param $expectedGenerationMethod
     */
    public function testGenerateImagePickGenerationMethodFromFileExtension(
        $sourceFilePath,
        $targetFilePath,
        $expectedGenerationMethod
    ) {
        $oGen = $this->getMock(DynamicImageGenerator::class, [
            '_getImageInfo',
            'validateGdVersion',
            'validateFileExist',
            '_isTargetPathValid',
            'validateImageFileExtension',
            '_generateJpg',
            '_generatePng',
            '_generateGif',
        ]);
        $oGen->expects($this->any())->method('_getImageInfo')->will($this->returnValue(array(100,100,75)));
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('_isTargetPathValid')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateImageFileExtension')->will($this->returnValue(true));
        $oGen->expects($this->once())->method($expectedGenerationMethod)->will($this->returnValue($targetFilePath));

        $oGen->UNITgenerateImage($sourceFilePath, $targetFilePath);
    }

    public function testGenerateImageThrowsException(): void
    {
        $sourceFilePath = 'source.jpg';
        $targetFilePath = 'target.jpg';
        $expectedException = StandardException::class;
        $expectedExceptionMessage = 'imageTarget path and generatedImage path differ';

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $oGen = $this->getMock(
            DynamicImageGenerator::class,
            [
             '_getImageInfo',
            'validateGdVersion',
            'validateFileExist',
            '_isTargetPathValid',
            'validateImageFileExtension',
            '_generateJpg',
            '_generatePng',
            '_generateGif',
            ]
        );
        $oGen->expects($this->any())->method('_getImageInfo')->will($this->returnValue(array(100,100,75)));
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('_isTargetPathValid')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateImageFileExtension')->will($this->returnValue(true));
        $oGen->expects($this->once())->method('_generateJpg')->will($this->returnValue('NOT_' . $targetFilePath));

        $oGen->UNITgenerateImage($sourceFilePath, $targetFilePath);
    }

    public function dataProviderTestGenerateImagePickGenerationMethodFromFileExtension(): array
    {
        return [
            ['sourceFile.jpeg', 'targetFile.jpeg', '_generateJpg'],
            ['sourceFile.jpg', 'targetFile.jpg', '_generateJpg'],
            ['sourceFile.png', 'targetFile.png', '_generatePng'],
            ['sourceFile.gif', 'targetFile.gif', '_generateGif'],
            // Test for case insensitivity
            ['sourceFile.JPEG', 'targetFile.jpeg', '_generateJpg'],
            ['sourceFile.JPG', 'targetFile.jpg', '_generateJpg'],
            ['sourceFile.PNG', 'targetFile.png', '_generatePng'],
            ['sourceFile.GIF', 'targetFile.gif', '_generateGif'],
        ];
    }

    public function __testGenerateImageFromSource(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, [
            '_getImageType',
            '_generatePng',
            '_generateJpg',
            '_generateGif',
            '_getImageUri',
            'validateGdVersion',
            'validateFileExist',
            '_isTargetPathValid',
            'validateImageFileExtension'
        ]);
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('_isTargetPathValid')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateImageFileExtension')->with('jpg')->will($this->returnValue(true));

        $oGen->expects($this->at(0))->method('_getImageType')->will($this->returnValue("png"));
        $oGen->expects($this->at(1))->method('_getImageUri')->will($this->returnValue("/test1/test2/test3/12_12_12/test.png"));
        $oGen->expects($this->at(2))->method('_generatePng')->with($this->equalTo("source"), $this->equalTo("target"), $this->equalTo("12"), $this->equalTo("12"))->will($this->returnValue("test.png"));

        $oGen->expects($this->at(3))->method('_getImageType')->will($this->returnValue("jpeg"));
        $oGen->expects($this->at(4))->method('_getImageUri')->will($this->returnValue("/test1/test2/test3/12_12_12/test.jpg"));
        $oGen->expects($this->at(5))->method('_generateJpg')->with($this->equalTo("source"), $this->equalTo("target"), $this->equalTo("12"), $this->equalTo("12"))->will($this->returnValue("test.jpg"));

        $oGen->expects($this->at(6))->method('_getImageType')->will($this->returnValue("jpeg"));
        $oGen->expects($this->at(7))->method('_getImageUri')->will($this->returnValue("/test1/test2/test3/12_12_12/test.jpeg"));
        $oGen->expects($this->at(8))->method('_generateJpg')->with($this->equalTo("source"), $this->equalTo("target"), $this->equalTo("12"), $this->equalTo("12"))->will($this->returnValue("test.jpg"));

        $oGen->expects($this->at(9))->method('_getImageType')->will($this->returnValue("gif"));
        $oGen->expects($this->at(10))->method('_getImageUri')->will($this->returnValue("/test1/test2/test3/12_12_12/test.gif"));
        $oGen->expects($this->at(11))->method('_generateGif')->with($this->equalTo("source"), $this->equalTo("target"), $this->equalTo("12"), $this->equalTo("12"))->will($this->returnValue("test.gif"));

        $oGen->expects($this->at(12))->method('_getImageType')->will($this->returnValue("unknown"));
        $oGen->expects($this->at(13))->method('_getImageUri')->will($this->returnValue("/test1/test2/test3/12_12_12/unknown"));

        $this->assertEquals("test.png", $oGen->UNITgenerateImage("source.jpg", "target.jpg"));
        $this->assertEquals("test.jpg", $oGen->UNITgenerateImage("source.jpg", "target.jpg"));
        $this->assertEquals("test.jpg", $oGen->UNITgenerateImage("source.jpg", "target.jpg"));
        $this->assertEquals("test.gif", $oGen->UNITgenerateImage("source.jpg", "target.jpg"));
        $this->assertFalse($oGen->UNITgenerateImage("source.jpg", "target.jpg"));
    }

    public function testGenerateImageGdVersionValidation(): void
    {
        $oGen = $this->getMock(DynamicImageGenerator::class, ['validateGdVersion']);
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(false));

        $this->assertFalse($oGen->UNITgenerateImage('source.jpg', 'target.jpg'));
    }

    public function testGenerateImageSourceFileExistValidation(): void
    {
        $oGen = $this->getMock(DynamicImageGenerator::class, ['validateGdVersion', 'validateFileExist']);
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(false));

        $this->assertFalse($oGen->UNITgenerateImage('source.jpg', 'target.jpg'));
    }

    public function testGenerateImageTargetPathValidation(): void
    {
        $oGen = $this->getMock(DynamicImageGenerator::class, ['validateGdVersion', 'validateFileExist', '_isTargetPathValid']);
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('_isTargetPathValid')->will($this->returnValue(false));

        $this->assertFalse($oGen->UNITgenerateImage('source.jpg', 'target.jpg'));
    }

    public function testGenerateImageFileExtensionValidationSource(): void
    {
        $oGen = $this->getMock(DynamicImageGenerator::class, ['validateGdVersion', 'validateFileExist', '_isTargetPathValid', 'validateImageFileExtension']);
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('_isTargetPathValid')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateImageFileExtension')->with('sourcejpg')->will($this->returnValue(false));

        $this->assertFalse($oGen->UNITgenerateImage('source.sourcejpg', 'target.jpg'));
    }

    public function testGenerateImageFileExtensionValidationTarget(): void
    {
        $oGen = $this->getMock(DynamicImageGenerator::class, ['validateGdVersion', 'validateFileExist', '_isTargetPathValid', 'validateImageFileExtension']);
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('_isTargetPathValid')->will($this->returnValue(true));
        $oGen
            ->method('validateImageFileExtension')
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->assertFalse($oGen->UNITgenerateImage('source.sourcejpg', 'target.targetjpg'));
    }

    public function testGenerateImageFileSourceAndTargetExtensionEqualityValidation(): void
    {
        $oGen = $this->getMock(DynamicImageGenerator::class, ['validateGdVersion', 'validateFileExist', '_isTargetPathValid', 'validateImageFileExtension']);
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('_isTargetPathValid')->will($this->returnValue(true));
        $oGen
            ->method('validateImageFileExtension')
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->assertFalse($oGen->UNITgenerateImage('source.jpg', 'target.png'));
    }

    public function testGenerateImageTargetFileExistsValidation(): void
    {
        $oGen = $this->getMock(DynamicImageGenerator::class, ['validateGdVersion', 'validateFileExist', '_isTargetPathValid', 'validateImageFileExtension', 'getImageDimensions', '_getImageInfo', '_generateJpg']);
        $oGen->expects($this->any())->method('validateGdVersion')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateFileExist')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('_isTargetPathValid')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('validateImageFileExtension')->will($this->returnValue(true));
        $oGen->expects($this->any())->method('getImageDimensions')->will($this->returnValue(array(100,100)));
        $oGen->expects($this->any())->method('_getImageInfo')->will($this->returnValue(array(100,100,75)));

        /** If an image file with the same dimensions already exist do regenerate it. I.e. never call _generateJpg' */
        $oGen->expects($this->never())->method('_generateJpg');
        $this->assertSame("target.jpg", $oGen->UNITgenerateImage('source.jpg', 'target.jpg'));
    }

    public function testGetImagePathNopicImageTarget(): void
    {
        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageMasterPath", "_getImageName", "_getImageTarget", "_getNopicImageTarget", "_generateImage", "_getImageType", "_setHeader", "_getHeaders"));

        $oGen->method('_getImageMasterPath')->will($this->returnValue("/test/"));
        $oGen->method('_getImageName')->will($this->returnValue("test.jpg"));
        $oGen->method('_getNopicImageTarget')->will($this->returnValue("nopicimagetarget"));
        $oGen->method('_setHeader')->with($this->equalTo("HTTP/1.1 404 Not Found"));

        $this->assertFalse($oGen->getImagePath());
    }

    public function testGetImagePath(): void
    {
        $sDir = basename($this->getConfig()->getPictureDir(false));

        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageMasterPath", "_getImageName", "_getImageTarget", "_getNopicImageTarget", "_generateImage", "_getImageType", "_setHeader", "_getHeaders"));

        $oGen->method('_getImageMasterPath')->will($this->returnValue("out/" . $sDir . "/master/"));
        $oGen->method('_getImageName')->will($this->returnValue("nopic.jpg"));
        $oGen->method('_getImageTarget')->will($this->returnValue("best.jpg"));
        $oGen->method('_generateImage')->will($this->returnValue("best.jpg"));
        $oGen->method('_getImageType')->will($this->returnValue("jpg"));

        $this->assertEquals("best.jpg", $oGen->getImagePath());
    }

    public function testGetImagePathWith404Header(): void
    {
        $sDir = basename($this->getConfig()->getPictureDir(false));

        $oGen = $this->getMock(\OxidEsales\Eshop\Core\DynamicImageGenerator::class, array("_getImageMasterPath", "_getImageName", "_getImageTarget", "_getNopicImageTarget", "_generateImage", "_getImageType", "_setHeader", "_getHeaders"));

        $oGen->method('_getImageMasterPath')->will($this->returnValue("out/" . $sDir . "/master/product/1/"));
        $oGen->method('_getImageName')->will($this->returnValue("best.jpg"));
        $oGen->method('_getNopicImageTarget')->will($this->returnValue("nopic.jpg"));
        $oGen->method('_setHeader')->with($this->equalTo("HTTP/1.1 404 Not Found"));
        $oGen->method('_generateImage')->will($this->returnValue("best.jpg"));
        $oGen->method('_getImageType')->will($this->returnValue("jpg"));

        $this->assertEquals("best.jpg", $oGen->getImagePath());
    }
}
