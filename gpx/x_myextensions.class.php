<?php

use phpGPX\Models\Extensions;

class MyExtension
{
    const NAMESPACE = "https://www.visugpx.com/editgpx/";
    const NAME = "TrkExtension";

    public $etape;

    public function __construct($etape)
    {
        $this->etape = $etape;
    }
}

class MyExtensionParser
{
    public static function parse(\SimpleXMLElement $node)
    {
        return new MyExtension((string) $node->etape);
    }

    public static function toXML(MyExtension $extension, \DOMDocument &$document)
    {
        $node =  $document->createElementNS(MyExtension::NAMESPACE, MyExtension::NAME);
        $etapeNode = $document->createElement("etape", $extension->etape);
        $node->appendChild($etapeNode);
        return $node;
    }
}

abstract class ExtensionParser
{
    public static $tagName = 'extensions';

    public static $usedNamespaces = [];

    /**
     * @param \SimpleXMLElement $nodes
     * @return Extensions
     */
    public static function parse($nodes)
    {
        $extensions = new Extensions();

        $nodeNamespaces = $nodes->getNamespaces(true);

        foreach ($nodeNamespaces as $key => $namespace) {
            switch ($namespace) {
                case MyExtension::NAMESPACE:
                    $node = $nodes->children($namespace)->{MyExtension::NAME};
                    if (!empty($node)) {
                        $extensions->myExtension = MyExtensionParser::parse($node);
                    }
                    break;
                default:
                    foreach ($nodes->children($namespace) as $child_key => $value) {
                        $extensions->unsupported[$key ? "$key:$child_key" : "$child_key"] = (string) $value;
                    }
            }
        }

        return $extensions;
    }


    /**
     * @param Extensions $extensions
     * @param \DOMDocument $document
     * @return \DOMElement|null
     */
    public static function toXML(Extensions $extensions, \DOMDocument &$document)
    {
        $node =  $document->createElement(self::$tagName);

        if (null !== $extensions->myExtension) {
            $child = MyExtensionParser::toXML($extensions->myExtension, $document);
            $node->appendChild($child);
        }

        if (!empty($extensions->unsupported)) {
            foreach ($extensions->unsupported as $key => $value) {
                $child = $document->createElement($key, $value);
                $node->appendChild($child);
            }
        }

        return $node;
    }
}
