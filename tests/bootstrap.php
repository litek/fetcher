<?php

spl_autoload_register(function($class) {
  return require __DIR__.'/../src/'.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
});
