<?php

declare(strict_types=1);

/**
 * This file is part of Esi\Pagination.
 *
 * Copyright (C) Eric Sizemore <https://www.secondversion.com/>.
 * Copyright (c) Ashley Dawson <ashley@ashleydawson.co.uk>.
 *
 * This source file is subject to the MIT license. For the full copyright,
 * license information, and credits/acknowledgements, please view the LICENSE
 * and README files that were distributed with this source code.
 */
/**
 * Esi\Pagination is a fork of AshleyDawson\SimplePagination (https://github.com/AshleyDawson/SimplePagination) which is:
 *     Copyright (c) 2015-2019 Ashley Dawson
 *
 * For a list of changes made in Esi\Pagination in comparison to the original library {@see CHANGELOG.md}.
 */

namespace Esi\Pagination\Exception;

use RuntimeException;

/**
 * Class CallbackNotFoundException.
 */
class CallbackNotFoundException extends RuntimeException {}
