<?php

/**
 * The declarations of the aliases registered in "src/polyfill.php".
 *
 * This file is never loaded: it is here so that an IDE knows the names the
 * sources were known under before they have been renamed, and reports them
 * as deprecated at the place they are used.
 */

declare(strict_types=1);

namespace Phplrt\Source {

    /**
     * @deprecated since phplrt 4.0 and will be removed in 5.0,
     *             please use {@see FileSource} instead.
     */
    final class File extends FileSource {}

    /**
     * @deprecated since phplrt 4.0 and will be removed in 5.0,
     *             please use {@see StringSource} instead.
     */
    final class Source extends StringSource {}

    /**
     * @deprecated since phplrt 4.0 and will be removed in 5.0,
     *             please use {@see ResourceSource} instead.
     */
    final class Stream extends ResourceSource {}

    /**
     * @deprecated since phplrt 4.0 and will be removed in 5.0,
     *             please use {@see VirtualStringSource} instead.
     */
    final class VirtualFile extends VirtualStringSource {}

    /**
     * @deprecated since phplrt 4.0 and will be removed in 5.0,
     *             please use {@see VirtualResourceSource} instead.
     */
    final class VirtualStreamingFile extends VirtualResourceSource {}

}
