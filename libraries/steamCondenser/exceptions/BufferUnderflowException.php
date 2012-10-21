<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * This exception class indicates a problem while reading from a
 * <var>ByteBuffer</var> instance, i.e. reading more data than the buffer
 * contains. This may hint at a protocol related problem.
 *
 * @author Sebastian Staudt
 * @package steam-condenser
 * @subpackage exceptions
 * @see ByteBuffer
 */
class BufferUnderflowException extends Exception {}
