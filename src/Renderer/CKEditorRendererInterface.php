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

namespace FOS\CKEditorBundle\Renderer;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
interface CKEditorRendererInterface
{
    public function renderTranslationPath(string $basePath): string;

    public function renderSize(array $config): string;

    public function renderJsPath(string $jsPath): string;

    /**
     * The available options are:
     *  - plugins: array.
     */
    public function renderWidget(string $id, array $config, array $options = []): string;
}
