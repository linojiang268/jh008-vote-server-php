<?php
namespace Jihe\Exceptions;

/**
 * Base exception class for system-wide exceptions.
 * 
 * 1) usage error is something that can be avoided by changing the code that calls 
 *    your routine. For example, if a routine gets into an error state when a null 
 *    is passed as one of its arguments (error condition usually represented by an 
 *    ArgumentNullException), the calling code can modified by the developer to ensure 
 *    that null is never passed. In other words the developer can ensure that usage 
 *    errors never occur.  ---- UncheckedException/RuntimeException
 * 
 * 2) Logical errors are system errors that can be handled programmatically. For 
 *    example, if File.Open throws FileNotFoundException, the calling code can catch 
 *    the exception and handle it by creating a new file. (Side note: this is in 
 *    contrast to the usage error described above where you would never first pass 
 *    a null argument, catch the NullArgumentException, and this time pass a non-null 
 *    argument).    --- CheckedException
 * 
 * 3) System failures are system errors that cannot be handled programmatically. For 
 *    example, you really cannot handle out of memory exception resulting from the 
 *    JIT running out of memory.  -- Error
 * 
 *   Generally, in a system there would be <1% of system failures, 5% logical errors, 
 *   and the rest ~95% are usage errors.
 *   
 *                           +--------------+
 *                           |  Exception   |
 *                           +--------------+
 *                                  ^        
 *                               <extends>   
 *                                  |        
 *        +-------+          +--------------+
 *        | Error |          | AppException |
 *        +-------+          +--------------+
 *        /  |  \             / |         \  
 *       \________/         \______/       \ 
 *                                       +------------------+
 *      unrecoverable        checked     | RuntimeException |
 *                                       +------------------+
 *                                         /   |    |      \
 *                                        \_________________/
 *                                                          
 *                                            unchecked
 */
class AppException extends \Exception
{
    public function __construct($message, $code = null)
    {
        parent::__construct($message, $code ?: ExceptionCode::GENERAL);
    }
}