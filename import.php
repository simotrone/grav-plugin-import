<?php
    namespace Grav\Plugin;

    use Grav\Common\Plugin;
    use Grav\Common\Utils;
    use Symfony\Component\Yaml\Yaml;

    class ImportPlugin extends Plugin
    {
        public static function getSubscribedEvents() {
            return [
                'onPageInitialized' => ['onPageInitialized', 0],
            ];
        }

        public function onPageInitialized()
        {
            if (property_exists($this->grav['page']->header(),'imports')) {

                $imports = $this->grav['page']->header()->imports;
                $parsed = [];

                if (is_array($imports)) {
                    foreach ($imports as $import) {
                        $res = $this->decodeFile($import);
                        $parsed[$res['basename']] = $res['decoded'];
                    }
                } else {
                    $res = $this->decodeFile($imports);
                    $parsed = $res['decoded'];
                }

                $this->grav['page']->header()->imports = $parsed;
            }
        }

        private function decodeFile($sourceFile) {
            $parts = pathinfo($sourceFile);
            $ret = array(
                'basename' => $parts['filename'],
                'decoded' => null,
            );

            $content = $this->getContents($sourceFile);
            if (!$content) {
                $this->grav['log']->error("$this->name | Cannot read '$sourceFile' file");
                return $ret;
            }

            $ext = $parts['extension'];
            if ($ext == 'yaml') {
                $ret['decoded'] = YAML::parse($content);
            } elseif ($ext == 'json') {
                $ret['decoded'] = json_decode($content, true);
            } else {
                $this->grav['log']->alert("$this->name | The extension '$ext' from file '$sourceFile' is not managed.");
            }
            return $ret;
        }

        private function getContents($fn) {
            if (strpos($fn, '://') !== false ){
                $path = $this->grav['locator']->findResource($fn, true);
            } else {
                $path = $this->grav['page']->path() . DS . $fn;
            }
            if (file_exists($path)) {
                return file_get_contents($path);
            }
            return null;
        }
    }
