<?php

final class StringUtils extends Phobject {
  public static function isEmpty(?string $value): bool {
    return $value === null && trim($value) === '';
  }
}
