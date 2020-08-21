<?php

/**
 * Remarkup Block Interpreter Extensions Collection
 *
 * File location: $phabricator_root/src/extensions/PhabricatorRemarkupBlockInterpreterExtentions.php
 *
 * @author: zengxs
 *
 * Remarkup Block Interpreter Collection
 *
 * Kroki Documentation: https://kroki.io/#how
 * Supported Diagrams:
 * - blockdiag
 * - seqdiag
 * - actdiag
 * - nwdiag
 * - packetdiag
 * - rackdiag
 * - c4plantuml
 * - ditaa
 * - erd
 * - graphviz
 * - mermaid
 * - nomnoml
 * - plantuml
 * - svgbob
 * - umlet
 * - vega
 * - vegalite
 * - wavedrom
 *
 */
abstract class PhabricatorRemarkupBaseBlockInterpreter extends PhutilRemarkupBlockInterpreter {
  protected static function parse_dimension($string) {
    $string = trim($string);
    return preg_match('/^(?:\d*\\.)?\d+%?$/', $string) ? $string : null;
  }

  protected static function base64_urlsafe_encode($string) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
  }

  protected static function fetch_svg_image($url, $width) {
    $future = id(new HTTPSFuture($url))
      ->setMethod('GET')
      ->setTimeout(5);
    list($status, $body, $headers) = $future->resolve();

    $img = phutil_tag(
      'img',
      array(
        'src' => 'data:image/svg+xml;base64,' . base64_encode($body),
        'width' => nonempty($width, null),
      ));
    return phutil_tag(
      'div',
      array(
        'class' => 'phabricator-remarkup-embed-image-full',
        'style' => 'display: inline;',
      ),
      $img);
  }
}