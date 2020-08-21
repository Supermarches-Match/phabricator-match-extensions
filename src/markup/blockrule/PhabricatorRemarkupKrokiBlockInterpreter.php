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

abstract class PhabricatorRemarkupKrokiBlockInterpreter extends PhabricatorRemarkupBaseBlockInterpreter {
  protected static function fetch_kroki_image($content, $diagram_type, $width) {
    $encoded = self::base64_urlsafe_encode(zlib_encode($content, ZLIB_ENCODING_DEFLATE, 9));
    $url = "https://kroki.io/".$diagram_type."/svg/".$encoded;
    return self::fetch_svg_image($url, $width);
  }

  public function markupContent($content, array $argv) {
    $width = self::parse_dimension(idx($argv, 'width'));

    return self::fetch_kroki_image($content, $this->getInterpreterName(), $width);
  }
}

final class PhabricatorRemarkupBlockdiagBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "blockdiag";
  }
}

final class PhabricatorRemarkupSeqdiagBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "seqdiag";
  }
}

final class PhabricatorRemarkupActdiagBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "actdiag";
  }
}

final class PhabricatorRemarkupNwdiagBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "nwdiag";
  }
}

final class PhabricatorRemarkupPacketdiagBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "packetdiag";
  }
}

final class PhabricatorRemarkupRackdiagBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "rackdiag";
  }
}

final class PhabricatorRemarkupC4PlantUMLBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "c4plantuml";
  }
}

final class PhabricatorRemarkupDitaaBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "ditaa";
  }
}

final class PhabricatorRemarkupErdBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "erd";
  }
}

final class PhabricatorRemarkupGraphvizBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "graphviz";
  }
}

final class PhabricatorRemarkupMermaidBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "mermaid";
  }
}

final class PhabricatorRemarkupNomnomlBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "nomnoml";
  }
}

final class PhabricatorRemarkupPlantUMLBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "plantuml";
  }
}

final class PhabricatorRemarkupSvgbobBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "svgbob";
  }
}

final class PhabricatorRemarkupUmletBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "umlet";
  }
}

final class PhabricatorRemarkupVegaBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "vega";
  }
}

final class PhabricatorRemarkupVegaLiteBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "vegalite";
  }
}

final class PhabricatorRemarkupWavedromBlockInterpreter extends PhabricatorRemarkupKrokiBlockInterpreter {
  public function getInterpreterName() {
    return "wavedrom";
  }
}
