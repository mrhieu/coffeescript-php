<?php

namespace CoffeeScript;

Init::init();

define('LEVEL_TOP',     1);
define('LEVEL_PAREN',   2);
define('LEVEL_LIST',    3);
define('LEVEL_COND',    4);
define('LEVEL_OP',      5);
define('LEVEL_ACCESS',  6);

define('TAB', '  ');

define('IDENTIFIER',  '/^[$A-Za-z_\x7f-\x{ffff}][$\w\x7f-\x{ffff}]*$/u');
define('IS_STRING',   '/^[\'"]/');
define('SIMPLENUM',   '/^[+-]?\d+$/');

class Nodes {

  static $utilities;

  static function multident($code, $tab)
  {
    return preg_replace('/\n/', "\n{$tab}", $code);
  }

  static function unfold_soak($options, $parent, $name)
  {
    if ( ! (isset($parent->{$name}) && $parent->{$name} && $ifn = $parent->{$name}->unfold_soak($options)))
    {
      return NULL;
    }

    $parent->{$name} = $ifn->body;
    $ifn->body = yy('Value', $parent);

    return $ifn;
  }

  static function utilities()
  {
    $utilities = array(
      'hasProp' => 'Object.prototype.hasOwnProperty',
      'slice'   => 'Array.prototype.slice'
    );

    $utilities['bind'] = <<<'BIND'
function(fn, me){ return function(){ return fn.apply(me, arguments); }; }
BIND;

    $utilities['extends'] = <<<'EXTENDS'
function(child, parent) {
  for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; }
  function ctor() { this.constructor = child; }
  ctor.prototype = parent.prototype;
  child.prototype = new ctor;
  child.__super__ = parent.prototype;
  return child;
}
EXTENDS;

    $utilities['indexOf'] = <<<'INDEXOF'
Array.prototype.indexOf || function(item) {
  for (var i = 0, l = this.length; i < l; i++) {
    if (this[i] === item) return i;
  }
  return -1;
}
INDEXOF;

    return $utilities;
  }

  static function utility($name)
  {
    if ( ! isset(self::$utilities))
    {
      self::$utilities = self::utilities();
    }

    Scope::$root->assign($ref = "__$name", self::$utilities[$name]);

    return $ref;
  }

  /**
   * Since PHP can't return values from __construct, and some of the node
   * classes rely heavily on this feature in JavaScript, we use this function
   * instead of 'new'.
   */
  static function yy($type)
  {
    $args = func_get_args();
    array_shift($args);

    $type = __NAMESPACE__.'\yy_'.$type;

    $inst = new $type;
    $inst = call_user_func_array(array($inst, 'constructor'), $args);

    return $inst;
  }

}

?>
