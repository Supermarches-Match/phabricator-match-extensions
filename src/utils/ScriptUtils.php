<?php

final class ScriptUtils extends Phobject {
  public static function separator(int $space = 0) : void {
    $line = '';
    for($i = 0; $i < $space; $i++){
      $line .= ' ';
    }
    for($i = 0; $i < 60-$space; $i++){
      $line .= '-';
    }
    $line .= "\n";
    echo $line;
  }
}
