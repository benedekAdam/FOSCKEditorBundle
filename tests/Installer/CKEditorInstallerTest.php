<?php

/*
 * This file is part of the FOSCKEditor Bundle.
 *
 * (c) 2018 - present  Friends of Symfony
 * (c) 2009 - 2017     Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\CKEditorBundle\Tests\Installer;

use FOS\CKEditorBundle\Exception\BadProxyUrlException;
use FOS\CKEditorBundle\Installer\CKEditorPredefinedBuild;
use FOS\CKEditorBundle\Installer\CKEditorInstaller;
use PHPUnit\Framework\TestCase;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class CKEditorInstallerTest extends TestCase
{
    /**
     * @var CKEditorInstaller
     */
    private $installer;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $proxy;

    protected function setUp(): void
    {
        $this->installer = new CKEditorInstaller();
        $this->path = __DIR__.'/../../src/Resources/public';
        $this->proxy = 'http://184.105.143.66:3128';

        $this->tearDown();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->path)) {
            exec('rm -rf '.$this->path);
        }
    }

    public function testInstall(): void
    {
        $this->installer->install();

        $this->assertInstall();
    }

    public function testInstallWithPath(): void
    {
        $this->path = sys_get_temp_dir().'/fos-ckeditor-installer-test';
        $this->installer->install(['path' => $this->path]);

        $this->assertInstall();
    }

    public function testInstallWithRelease(): void
    {
        $this->installer->install($options = ['release' => CKEditorPredefinedBuild::RELEASE_CLASSIC]);

        $this->assertInstall($options);
    }

    public function testInstallWithCustomBuild(): void
    {
        $this->installer->install($options = ['release' => CKEditorPredefinedBuild::RELEASE_CUSTOM, 'custom_build_id' => 'ffbb0c61721cb8543bfa54315374592d']);

        $this->assertInstall($options);
    }

    public function testInstallWithCustomBuildWithInvalidVersion(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Specifying version for custom build is not supported/');

        $this->installer->install(['release' => CKEditorInstaller::RELEASE_CUSTOM, 'custom_build_id' => 'ffbb0c61721cb8543bfa54315374592d', 'version' => '4.11.4']);
    }

    public function testInstallWithCustomBuildWithMissingId(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Custom build ID is not specified/');

        $this->installer->install(['release' => CKEditorInstaller::RELEASE_CUSTOM]);
    }

    public function testInstallWithVersion(): void
    {
        $this->installer->install($options = ['version' => '41.0.0']);

        $this->assertInstall($options);
    }

    public function testInstallWithExcludes(): void
    {
        $this->installer->install($options = ['excludes' => ['adapters', 'samples']]);

        $this->assertInstall($options);
    }

    /**
     * @group proxy
     */
    public function testInstallWithHttpProxy(): void
    {
        putenv('http_proxy='.$this->proxy);
        $this->installer->install();
        putenv('http_proxy');

        $this->assertInstall();
    }

    /**
     * @group proxy
     */
    public function testInstallWithHttpsProxy(): void
    {
        putenv('https_proxy='.$this->proxy);
        $this->installer->install();
        putenv('https_proxy');

        $this->assertInstall();
    }

    /**
     * @group proxy
     */
    public function testInstallWithHttpProxyRequestFullUri()
    {
        putenv('http_proxy='.$this->proxy);
        putenv('http_proxy_request_fulluri=true');

        $this->installer->install();

        putenv('http_proxy');
        putenv('http_proxy_request_fulluri');

        $this->assertInstall();
    }

    /**
     * @group proxy
     */
    public function testInstallWithHttpsProxyRequestFullUri(): void
    {
        putenv('https_proxy='.$this->proxy);
        putenv('https_proxy_request_fulluri=true');

        $this->installer->install();

        putenv('https_proxy');
        putenv('https_proxy_request_fulluri');

        $this->assertInstall();
    }

    /**
     * @group proxy
     */
    public function testInstallWithProxyUrlMissingHost(): void
    {
        putenv('http_proxy=notgonnahappen');

        $this->expectException(BadProxyUrlException::class);
        $this->installer->install();
    }

    /**
     * @group proxy
     */
    public function testInstallWithProxyUrlMissingPort(): void
    {
        putenv('http_proxy=http://notgonnahappen.com');

        $this->expectException(BadProxyUrlException::class);
        $this->installer->install();
    }

    public function testReinstall(): void
    {
        $this->installer->install();
        $this->installer->install();

        $this->assertInstall();
    }

    public function testReinstallWithClearDrop(): void
    {
        $this->installer->install();
        $this->installer->install($options = [
            'release' => CKEditorPredefinedBuild::RELEASE_CLASSIC,
            'clear' => CKEditorPredefinedBuild::CLEAR_DROP,
        ]);

        $this->assertInstall($options);
    }

    public function testReinstallWithClearKeep(): void
    {
        $this->installer->install(['release' => CKEditorPredefinedBuild::RELEASE_CLASSIC]);
        $this->installer->install($options = [
            'version' => '41.0.0',
            'release' => CKEditorPredefinedBuild::RELEASE_CLASSIC,
            'clear' => CKEditorPredefinedBuild::CLEAR_KEEP,
        ]);

        $this->assertInstall($options);
    }

    public function testReinstallWithClearSkip(): void
    {
        $this->installer->install($options = ['version' => '41.0.0']);
        $this->installer->install(['clear' => CKEditorPredefinedBuild::CLEAR_SKIP]);

        $this->assertInstall($options);
    }

    private function assertInstall(array $options = []): void
    {
        $this->assertFileExists($this->path.'/ckeditor.js');

        if (CKEditorPredefinedBuild::RELEASE_CUSTOM === ($options['release'] ?? '')) {
            // todo
//            $this->assertStringContainsString($options['custom_build_id'], file_get_contents($this->path.'/build-config.js'));
        } else {
            if (isset($options['release'])) {
                $this->assertRelease($options['release']);
            }

            if (isset($options['version'])) {
                $this->assertVersion($options['version']);
            }
        }

        if (!isset($options['excludes'])) {
            $options['excludes'] = ['samples'];
        }

        $this->assertExcludes($options['excludes']);
    }

    private function assertRelease(string $release): void
    {
        switch ($release) {
            case CKEditorPredefinedBuild::RELEASE_CLASSIC:
                $this->assertFileExists($this->path.'/plugins/copyformatting');

                break;

            case CKEditorPredefinedBuild::RELEASE_BALLON:
                $this->assertFileExists($this->path.'/plugins/link');
                $this->assertFileDoesNotExist($this->path.'/plugins/image');

                break;

            case CKEditorPredefinedBuild::RELEASE_DOCUMENT:
                $this->assertFileExists($this->path.'/plugins/image');
                $this->assertFileDoesNotExist($this->path.'/plugins/copyformatting');

                break;
        }
    }

    private function assertVersion(string $version): void
    {
        $package = json_decode(file_get_contents($this->path.'/package.json'), true);

        $this->assertIsArray($package);
        $this->assertArrayHasKey('version', $package);
        $this->assertSame($version, $package['version']);
    }

    /**
     * @param string[] $excludes
     */
    private function assertExcludes(array $excludes): void
    {
        foreach ($excludes as $exclude) {
            $this->assertFileDoesNotExist($this->path.'/'.$exclude);
        }
    }
}
