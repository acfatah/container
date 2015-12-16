<?php

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright (c) 2015, Achmad F. Ibrahim
 * @link https://github.com/acfatah/container
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */

namespace Acfatah\Container;

use Closure;
use ReflectionFunction;
use RuntimeException;
use Serializable as SerializableInterface;
use SplFileObject;

/**
 * Wrapper class to enable serialization of closure.
 *
 * @link http://www.htmlist.com/development/extending-php-5-3-closures-with-serialization-and-reflection/
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */
final class SerializableClosure implements SerializableInterface
{
    /**
     * @var \ReflectionFunction
     */
    private $reflection;

    /**
     * Constructor.
     *
     * @param \Closure $closure
     */
    public function __construct(Closure $closure)
    {
        $this->reflection = new ReflectionFunction($closure);
    }

    /**
     * Invokes the closure instance.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return $this->reflection->invokeArgs(func_get_args());
    }

    /**
     * Gets the closure instance.
     *
     * @return \Closure
     */
    public function getClosure()
    {
        return $this->reflection->getClosure();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        // prepare code
        $file = new SplFileObject($this->reflection->getFileName());
        $file->seek($this->reflection->getStartLine()-1);
        $code = '';
        while ($file->key() < $this->reflection->getEndLine()) {
            $code .= $file->current();
            $file->next();
        }
        $start = strpos($code, 'function');
        $code = substr($code, $start, strpos($code, '}') - $start + 1);

        // prepare variables
        $variables = [];
        $index = stripos($code, 'use');
        // if 'use' keyword found
        if (false !== $index) {
            // get the names of the variables inside the use statement
            $start = strpos($code, '(', $index) + 1;
            $end = strpos($code, ')', $start);
            $use_variables = explode(',', substr($code, $start, $end - $start));
            $static_variables = $this->reflection->getStaticVariables();
            // keep only the variables that appeared in both scopes
            foreach ($use_variables as $variable) {
                $variable = trim($variable, '$&');
                $variables[$variable] = $static_variables[$variable];
            }
        }

        return serialize(['code' => $code, 'variables' => $variables]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        extract($data['variables']);
        eval('$__closure = ' . $data['code'] . ';');
        if (isset($__closure) && $__closure instanceof Closure) {
            $this->reflection = new ReflectionFunction($__closure);
        } else {
            // throw exception if eval fail
            throw new RuntimeException('Unable to unserialize data!'); // @codeCoverageIgnore
        }
    }
}
