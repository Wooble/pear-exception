<?php
class PEAR_PackageFile_Generator_v1
{
    /**
     * @var PEAR_PackageFile_v1
     */
    var $_packagefile;
    function PEAR_PackageFile_Generator_v1(&$packagefile)
    {
        $this->_packagefile = &$packagefile;
    }

    /**
     * Return an XML document based on the package info (as returned
     * by the PEAR_Common::infoFrom* methods).
     *
     * @return string XML data
     */
    function toXml()
    {
        if (!$this->_packagefile->validate()) {
            return false;
        }
        $pkginfo = $this->_packagefile->toArray();
        static $maint_map = array(
            "handle" => "user",
            "name" => "name",
            "email" => "email",
            "role" => "role",
            );
        $ret = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
        $ret .= "<!DOCTYPE package SYSTEM \"http://pear.php.net/dtd/package-1.0\">\n";
        $ret .= "<package version=\"1.0\" packagerversion=\"@PEAR-VER@\">
  <name>$pkginfo[package]</name>
  <summary>".htmlspecialchars($pkginfo['summary'])."</summary>
  <description>".htmlspecialchars($pkginfo['description'])."</description>
  <maintainers>
";
        foreach ($pkginfo['maintainers'] as $maint) {
            $ret .= "    <maintainer>\n";
            foreach ($maint_map as $idx => $elm) {
                $ret .= "      <$elm>";
                $ret .= htmlspecialchars($maint[$idx]);
                $ret .= "</$elm>\n";
            }
            $ret .= "    </maintainer>\n";
        }
        $ret .= "  </maintainers>\n";
        $ret .= $this->_makeReleaseXml($pkginfo);
        if (@sizeof($pkginfo['changelog']) > 0) {
            $ret .= "  <changelog>\n";
            foreach ($pkginfo['changelog'] as $oldrelease) {
                $ret .= $this->_makeReleaseXml($oldrelease, true);
            }
            $ret .= "  </changelog>\n";
        }
        $ret .= "</package>\n";
        return $ret;
    }

    // }}}
    // {{{ _makeReleaseXml()

    /**
     * Generate part of an XML description with release information.
     *
     * @param array  $pkginfo    array with release information
     * @param bool   $changelog  whether the result will be in a changelog element
     *
     * @return string XML data
     *
     * @access private
     */
    function _makeReleaseXml($pkginfo, $changelog = false)
    {
        // XXX QUOTE ENTITIES IN PCDATA, OR EMBED IN CDATA BLOCKS!!
        $indent = $changelog ? "  " : "";
        $ret = "$indent  <release>\n";
        if (!empty($pkginfo['version'])) {
            $ret .= "$indent    <version>$pkginfo[version]</version>\n";
        }
        if (!empty($pkginfo['release_date'])) {
            $ret .= "$indent    <date>$pkginfo[release_date]</date>\n";
        }
        if (!empty($pkginfo['release_license'])) {
            $ret .= "$indent    <license>$pkginfo[release_license]</license>\n";
        }
        if (!empty($pkginfo['release_state'])) {
            $ret .= "$indent    <state>$pkginfo[release_state]</state>\n";
        }
        if (!empty($pkginfo['release_notes'])) {
            $ret .= "$indent    <notes>".htmlspecialchars($pkginfo['release_notes'])."</notes>\n";
        }
        if (!empty($pkginfo['release_warnings'])) {
            $ret .= "$indent    <warnings>".htmlspecialchars($pkginfo['release_warnings'])."</warnings>\n";
        }
        if (isset($pkginfo['release_deps']) && sizeof($pkginfo['release_deps']) > 0) {
            $ret .= "$indent    <deps>\n";
            foreach ($pkginfo['release_deps'] as $dep) {
                $ret .= "$indent      <dep type=\"$dep[type]\" rel=\"$dep[rel]\"";
                if (isset($dep['version'])) {
                    $ret .= " version=\"$dep[version]\"";
                }
                if (isset($dep['optional'])) {
                    $ret .= " optional=\"$dep[optional]\"";
                }
                if (isset($dep['name'])) {
                    $ret .= ">$dep[name]</dep>\n";
                } else {
                    $ret .= "/>\n";
                }
            }
            $ret .= "$indent    </deps>\n";
        }
        if (isset($pkginfo['configure_options'])) {
            $ret .= "$indent    <configureoptions>\n";
            foreach ($pkginfo['configure_options'] as $c) {
                $ret .= "$indent      <configureoption name=\"".
                    htmlspecialchars($c['name']) . "\"";
                if (isset($c['default'])) {
                    $ret .= " default=\"" . htmlspecialchars($c['default']) . "\"";
                }
                $ret .= " prompt=\"" . htmlspecialchars($c['prompt']) . "\"";
                $ret .= "/>\n";
            }
            $ret .= "$indent    </configureoptions>\n";
        }
        if (isset($pkginfo['provides'])) {
            foreach ($pkginfo['provides'] as $key => $what) {
                $ret .= "$indent    <provides type=\"$what[type]\" ";
                $ret .= "name=\"$what[name]\" ";
                if (isset($what['extends'])) {
                    $ret .= "extends=\"$what[extends]\" ";
                }
                $ret .= "/>\n";
            }
        }
        if (isset($pkginfo['filelist'])) {
            $ret .= "$indent    <filelist>\n";
            foreach ($pkginfo['filelist'] as $file => $fa) {
                @$ret .= "$indent      <file role=\"$fa[role]\"";
                if (isset($fa['baseinstalldir'])) {
                    $ret .= ' baseinstalldir="' .
                        htmlspecialchars($fa['baseinstalldir']) . '"';
                }
                if (isset($fa['md5sum'])) {
                    $ret .= " md5sum=\"$fa[md5sum]\"";
                }
                if (isset($fa['platform'])) {
                    $ret .= " platform=\"$fa[platform]\"";
                }
                if (!empty($fa['install-as'])) {
                    $ret .= ' install-as="' .
                        htmlspecialchars($fa['install-as']) . '"';
                }
                $ret .= ' name="' . htmlspecialchars($file) . '"';
                if (empty($fa['replacements'])) {
                    $ret .= "/>\n";
                } else {
                    $ret .= ">\n";
                    foreach ($fa['replacements'] as $r) {
                        $ret .= "$indent        <replace";
                        foreach ($r as $k => $v) {
                            $ret .= " $k=\"" . htmlspecialchars($v) .'"';
                        }
                        $ret .= "/>\n";
                    }
                    @$ret .= "$indent      </file>\n";
                }
            }
            $ret .= "$indent    </filelist>\n";
        }
        $ret .= "$indent  </release>\n";
        return $ret;
    }
    // {{{ _unIndent()

    /**
     * Unindent given string (?)
     *
     * @param string $str The string that has to be unindented.
     * @return string
     * @access private
     */
    function _unIndent($str)
    {
        // remove leading newlines
        $str = preg_replace('/^[\r\n]+/', '', $str);
        // find whitespace at the beginning of the first line
        $indent_len = strspn($str, " \t");
        $indent = substr($str, 0, $indent_len);
        $data = '';
        // remove the same amount of whitespace from following lines
        foreach (explode("\n", $str) as $line) {
            if (substr($line, 0, $indent_len) == $indent) {
                $data .= substr($line, $indent_len) . "\n";
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    function toArray()
    {
        if (!$this->_packagefile->validate(PEAR_VALIDATE_NORMAL)) {
            return false;
        }
        return $this->_packagefile->getArray();
    }

    /**
     * Convert a package.xml version 1.0 into version 2.0
     *
     * Note that this does a basic conversion, to allow more advanced
     * features like bundles and multiple releases
     * @return PEAR_PackageFile_v2
     */
    function &toV2()
    {
        $arr = array(
            'name' => array(
                'attribs' => array(
                        'channel' => 'pear',
                    ),
                '_content' => $this->_packagefile->getPackage(),
            )
        );
        if ($extends = $this->_packagefile->getExtends()) {
            $arr['extends'] = $extends;
        }
        $arr['summary'] = $this->_packagefile->getSummary();
        $arr['description'] = $this->_packagefile->getDescription();
        $maintainers = $this->_packagefile->getMaintainers();
        foreach ($maintainers as $maintainer) {
            if ($maintainer['role'] != 'lead') {
                continue;
            }
            unset($maintainer['role']);
            $maintainer['active'] = 'yes';
            $maintainer['user'] = $maintainer['handle'];
            unset($maintainer['handle']);
            $arr['lead'][] = array('attribs' => $maintainer);
        }
        if (count($arr['lead']) == 1) {
            $arr['lead'] = $arr['lead'][0];
        }
        foreach ($maintainers as $maintainer) {
            if ($maintainer['role'] == 'lead') {
                continue;
            }
            $maintainer['active'] = 'yes';
            $maintainer['user'] = $maintainer['handle'];
            unset($maintainer['handle']);
            $arr['maintainer'][] = array('attribs' => $maintainer);
        }
        if (isset($arr['maintainer']) && count($arr['maintainer']) == 1) {
            $arr['maintainer'] = $arr['maintainer'][0];
        }
        $arr['date'] = $this->_packagefile->getDate();
        $arr['version'] = array(
                'attribs' =>
                    array('api' => $this->_packagefile->getVersion(),
                          'package' => $this->_packagefile->getVersion()
                         )
            );
        $arr['stability'] = array(
                'attribs' =>
                    array('api' => $this->_packagefile->getState(),
                          'package' => $this->_packagefile->getState()
                         )
            );
        $licensemap =
            array(
                'php license' => 'http://www.php.net/license/3_0.txt',
                'lgpl' => 'http://www.gnu.org/copyleft/lesser.html',
                'bsd' => 'http://www.opensource.org/licenses/bsd-license.php',
                'mit' => 'http://www.opensource.org/licenses/mit-license.php',
                'gpl' => 'http://www.gnu.org/copyleft/gpl.html',
                'apache' => 'http://www.opensource.org/licenses/apache2.0.php'
            );
        if (isset($licensemap[strtolower($this->_packagefile->getLicense())])) {
            $uri = $licensemap[strtolower($this->_packagefile->getLicense())];
        } else {
            $uri = 'http://www.example.com';
        }
        $arr['license'] = array(
            'attribs' => array('uri' => $uri),
            '_content' => $this->_packagefile->getLicense()
            );
        $arr['notes'] = $this->_packagefile->getNotes();
        $temp = array();
        $arr['contents'] = $this->_convertFilelist2_0($temp);
        $this->_convertDependencies2_0($arr);
        $release = $this->_packagefile->getConfigureOptions() ?
            'extsrcrelease' : 'phprelease';
        $arr[$release] = array();
        $this->_convertRelease2_0($arr[$release], $temp);
        if ($cl = $this->_packagefile->getChangelog()) {
            foreach ($cl as $release) {
                $rel = array();
                $rel['version'] = array(
                        'attribs' =>
                            array('api' => $release['version'],
                                  'package' => $release['version']
                                 )
                    );
                $rel['stability'] = array(
                        'attribs' =>
                            array('api' => $release['release_state'],
                                  'package' => $release['release_state']
                                 )
                    );
                $rel['date'] = $release['release_date'];
                if (isset($release['release_license'])) {
                    if (isset($licensemap[strtolower($release['release_license'])])) {
                        $uri = $licensemap[strtolower($release['release_license'])];
                    } else {
                        $uri = 'http://www.example.com';
                    }
                    $rel['license'] = array(
                            'attribs' => array('uri' => $uri),
                            '_content' => $release['release_license']
                        );
                } else {
                    $rel['license'] = $arr['license'];
                }
                $rel['notes'] = $release['release_notes'];
                $arr['changelog']['release'][] = $rel;
            }
        }
        include_once 'PEAR/PackageFile/v2.php';
        $ret = new PEAR_PackageFile_v2;
        $ret->fromArray($arr);
        return $ret;
    }

    function _convertFilelist2_0(&$package)
    {
        $ret = array('dir' =>
                    array(
                        'attribs' => array('name' => '/'),
                        'file' => array()
                        )
                    );
        $package['platform'] =
        $package['osmap'] =
        $package['install-as'] = array();
        foreach ($this->_packagefile->getFilelist() as $name => $file) {
            $file['name'] = $name;
            if (isset($file['replacements'])) {
                $repl = $file['replacements'];
                unset($file['replacements']);
            } else {
                unset($repl);
            }
            if (isset($file['install-as'])) {
                $package['install-as'][$name] = $file['install-as'];
                unset($file['install-as']);
            }
            if (isset($file['platform'])) {
                $package['platform'][$name] = $file['platform'];
                $package['osmap'][$file['platform']][] = $name;
                unset($file['platform']);
            }
            $file = array('attribs' => $file);
            if (isset($repl)) {
                foreach ($repl as $replace ) {
                    $file['tasks:replace'][] = array('attribs' => $replace);
                }
                if (count($repl) == 1) {
                    $file['tasks:replace'] = $file['tasks:replace'][0];
                }
            }
            $ret['dir']['file'][] = $file;
        }
        return $ret;
    }

    function _convertDependencies2_0(&$release)
    {
        $peardep = array('pearinstaller' =>
            array('attribs' =>
                array('min' => '@PEAR-VER@')));
        $required = $optional = array();
        $release['dependencies'] = array();
        if ($this->_packagefile->hasDeps()) {
            foreach ($this->_packagefile->getDeps() as $dep) {
                if (!isset($dep['optional']) || $dep['optional'] == 'no') {
                    $required[] = $dep;
                } else {
                    $optional[] = $dep;
                }
            }
            foreach (array('required', 'optional') as $arr) {
                $deps = array();
                foreach ($$arr as $dep) {
                    // organize deps by dependency type and name
                    if (!isset($deps[$dep['type']])) {
                        $deps[$dep['type']] = array();
                    }
                    if (isset($dep['name'])) {
                        $deps[$dep['type']][$dep['name']][] = $dep;
                    } else {
                        $deps[$dep['type']][] = $dep;
                    }
                }
                do {
                    if (isset($deps['php'])) {
                        $php = array();
                        if (count($deps['php']) > 1) {
                            $php = $this->_processMultipleDeps($deps['php']);
                        } else {
                            $php = $this->_processDep($deps['php'][0]);
                            if (!$php) {
                                break; // poor mans throw
                            }
                        }
                        $release['dependencies'][$arr]['php'] = $php;
                    }
                } while (false);
                do {
                    if (isset($deps['pkg'])) {
                        $pkg = array();
                        $pkg = $this->_processMultipleDepsName($deps['pkg']);
                        if (!$pkg) {
                            break; // poor mans throw
                        }
                        $release['dependencies'][$arr]['package'] = $pkg;
                    }
                } while (false);
                do {
                    if (isset($deps['ext'])) {
                        $pkg = array();
                        $pkg = $this->_processMultipleDepsName($deps['ext']);
                        $release['dependencies'][$arr]['extension'] = $pkg;
                    }
                } while (false);
                // skip sapi - it's not supported so nobody will have used it
                // skip os - it's not supported in 1.0
            }
        }
        if (isset($release['dependencies']['required'])) {
            $release['dependencies']['required'] =
                array_merge($peardep, $release['dependencies']['required']);
        } else {
            $release['dependencies']['required'] = $peardep;
        }
        if (isset($release['dependencies']['optional'])) {
            $release['dependencies']['group'] =
                $release['dependencies']['optional'];
            unset($release['dependencies']['optional']);
            $release['dependencies']['group']['attribs']['name'] = 'optional';
            $release['dependencies']['group']['attribs']['hint'] =
                'optional dependencies - you can define custom groups of optional deps';
        }
    }

    function _convertRelease2_0(&$release, $package)
    {
        if (count($package['platform']) || count($package['install-as'])) {
            $generic = array();
            foreach ($package['install-as'] as $file => $as) {
                if (!isset($package['platform'][$file])) {
                    $generic[] = $file;
                }
            }
            if (count($package['platform'])) {
                $oses = array();
                foreach ($package['platform'] as $file => $os) {
                    $oses[$os] = count($oses);
                    $release[$oses[$os]]['installconditions']
                        ['os']['attribs']['pattern'] = $os;
                    if (isset($package['install-as'][$file])) {
                        $release[$oses[$os]]['filelist']['install'][] =
                        array('attribs' => 
                            array('name' => $file,
                                  'as' => $package['install-as'][$file]));
                    }
                    foreach ($generic as $file) {
                        $release[$oses[$os]]['filelist']['install'][] =
                        array('attribs' => 
                            array('name' => $file,
                                  'as' => $package['install-as'][$file]));
                    }
                }
                if (count($generic)) {
                    $release[count($oses)]['installconditions']['os']
                        ['attribs']['pattern'] = '*';
                    foreach ($generic as $file) {
                        $release[count($oses)]['filelist']['install'][] =
                        array('attribs' => 
                            array('name' => $file,
                                  'as' => $package['install-as'][$file]));
                    }
                    foreach ($package['osmap'] as $os => $files) {
                        foreach ($files as $file) {
                            $release[count($oses)]['filelist']['ignore'][]
                                ['attribs']['name'] = $file;
                        }
                    }
                }
                foreach ($package['osmap'] as $os => $files) {
                    foreach ($oses as $os1 => $i) {
                        if ($os1 == $os) {
                            continue;
                        }
                        foreach ($files as $file) {
                            $release[$i]['filelist']['ignore'][]
                                ['attribs']['name'] = $file;
                        }
                    }
                }
                // cleanup
                foreach ($release as $i => $rel) {
                    if (isset($rel['filelist']['install']) &&
                          count($rel['filelist']['install']) == 1) {
                        $release[$i]['filelist']['install'] =
                            $release[$i]['filelist']['install'][0];
                    }
                    if (isset($rel['filelist']['ignore']) &&
                          count($rel['filelist']['ignore']) == 1) {
                        $release[$i]['filelist']['ignore'] =
                            $release[$i]['filelist']['ignore'][0];
                    }
                }
            } else {
                $release['installconditions']['os']['attribs']['pattern'] = '*';
                foreach ($package['install-as'] as $file => $value) {
                    if (count($package['install-as']) > 1) {
                        $release['filelist']['install'][] =
                            array('attribs' =>
                                array('name' => $file,
                                      'as' => $value));
                    } else {
                        $release['filelist']['install'] =
                            array('attribs' =>
                                array('name' => $file,
                                      'as' => $value));
                    }
                }
            }
        }
    }

    function _processDep($dep)
    {
        if ($dep['type'] == 'php') {
            if ($dep['rel'] == 'has') {
                // come on - everyone has php!
                return false;
            }
        }
        $php = array();
        if ($dep['type'] != 'php') {
            $php['attribs']['name'] = $dep['name'];
            if ($dep['type'] == 'pkg') {
                // no way to guess for extensions, so we'll assume they
                // are NOT pecl
                $php['attribs']['channel'] = 'pear';
            }
        }
        switch ($dep['rel']) {
            case 'gt' :
                $php['exclude']['attribs']['version'] = $dep['version'];
            case 'ge' :
                $php['attribs']['min'] = $dep['version'];
            break;
            case 'lt' :
                $php['exclude']['attribs']['version'] = $dep['version'];
            case 'le' :
                $php['attribs']['max'] = $dep['version'];
            break;
            case 'eq' :
                $php['attribs']['max'] = $dep['version'];
                $php['attribs']['min'] = $dep['version'];
            break;
            case 'not' :
                $php['exclude']['attribs']['version'] = $dep['version'];
            break;
        }
        return $php;
    }

    function _processMultipleDeps($deps)
    {
        $test = array();
        foreach ($deps as $dep) {
            $test[] = $this->_processDep($dep);
        }
        $min = array();
        $max = array();
        foreach ($test as $dep) {
            if (!dep) {
                continue;
            }
            if (isset($dep['attribs']['min'])) {
                $min[$dep['attribs']['min']] = count($min);
            }
            if (isset($dep['attribs']['max'])) {
                $max[$dep['attribs']['max']] = count($max);
            }
        }
        if (count($min) > 0) {
            uksort($min, 'version_compare');
        }
        if (count($max) > 0) {
            uksort($max, 'version_compare');
        }
        if (count($min)) {
            // get the highest minimum
            $min = array_pop(array_flip($min));
        } else {
            $min = false;
        }
        if (count($max)) {
            // get the lowest maximum
            $max = array_shift(array_flip($max));
        } else {
            $max = false;
        }
        if ($min) {
            $php['attribs']['min'] = $min;
        }
        if ($max) {
            $php['attribs']['max'] = $max;
        }
        $exclude = array();
        foreach ($test as $dep) {
            if (!isset($dep['exclude'])) {
                continue;
            }
            $exclude[] = $dep['exclude'];
        }
        if (count($exclude)) {
            $php['exclude'] = $exclude;
        }
        return $php;
    }

    function _processMultipleDepsName($deps)
    {
        $tests = array();
        foreach ($deps as $name => $dep) {
            foreach ($dep as $d) {
                $tests[$name][] = $this->_processDep($d);
            }
        }
        foreach ($tests as $name => $test) {
            $php = array();
            $min = array();
            $max = array();
            foreach ($test as $dep) {
                if (!$dep) {
                    continue;
                }
                if (isset($dep['attribs']['min'])) {
                    $min[$dep['attribs']['min']] = count($min);
                }
                if (isset($dep['attribs']['max'])) {
                    $max[$dep['attribs']['max']] = count($max);
                }
            }
            if (isset($dep['attribs']['channel'])) {
                $php['attribs']['channel'] = $dep['attribs']['channel'];
            }
            $php['attribs']['name'] = $name;
            if (count($min) > 0) {
                uksort($min, 'version_compare');
            }
            if (count($max) > 0) {
                uksort($max, 'version_compare');
            }
            if (count($min)) {
                // get the highest minimum
                $min = array_pop(array_flip($min));
            } else {
                $min = false;
            }
            if (count($max)) {
                // get the lowest maximum
                $max = array_shift(array_flip($max));
            } else {
                $max = false;
            }
            if ($min) {
                $php['attribs']['min'] = $min;
            }
            if ($max) {
                $php['attribs']['max'] = $max;
            }
            $exclude = array();
            foreach ($test as $dep) {
                if (!isset($dep['exclude'])) {
                    continue;
                }
                $exclude[] = $dep['exclude'];
            }
            if (count($exclude)) {
                $php['exclude'] = $exclude;
            }
            $ret[] = $php;
        }
        return $ret;
    }

    /**
     * Build a "provides" array from data returned by
     * analyzeSourceCode().  The format of the built array is like
     * this:
     *
     *  array(
     *    'class;MyClass' => 'array('type' => 'class', 'name' => 'MyClass'),
     *    ...
     *  )
     *
     *
     * @param array $srcinfo array with information about a source file
     * as returned by the analyzeSourceCode() method.
     *
     * @return void
     *
     * @access private
     *
     */
    function _buildProvidesArray($srcinfo)
    {
        if (!$this->_isValid) {
            return false;
        }
        $file = basename($srcinfo['source_file']);
        $pn = $this->_packageInfo['package'];
        $pnl = strlen($pn);
        foreach ($srcinfo['declared_classes'] as $class) {
            $key = "class;$class";
            if (isset($this->_packageInfo['provides'][$key])) {
                continue;
            }
            $this->_packageInfo['provides'][$key] =
                array('file'=> $file, 'type' => 'class', 'name' => $class);
            if (isset($srcinfo['inheritance'][$class])) {
                $this->_packageInfo['provides'][$key]['extends'] =
                    $srcinfo['inheritance'][$class];
            }
        }
        foreach ($srcinfo['declared_methods'] as $class => $methods) {
            foreach ($methods as $method) {
                $function = "$class::$method";
                $key = "function;$function";
                if ($method{0} == '_' || !strcasecmp($method, $class) ||
                    isset($this->_packageInfo['provides'][$key])) {
                    continue;
                }
                $this->_packageInfo['provides'][$key] =
                    array('file'=> $file, 'type' => 'function', 'name' => $function);
            }
        }

        foreach ($srcinfo['declared_functions'] as $function) {
            $key = "function;$function";
            if ($function{0} == '_' || isset($this->_packageInfo['provides'][$key])) {
                continue;
            }
            if (!strstr($function, '::') && strncasecmp($function, $pn, $pnl)) {
                $warnings[] = "in1 " . $file . ": function \"$function\" not prefixed with package name \"$pn\"";
            }
            $this->_packageInfo['provides'][$key] =
                array('file'=> $file, 'type' => 'function', 'name' => $function);
        }
    }

    // }}}
}
//set_include_path('C:/devel/pear_with_channels');
//require_once 'PEAR/PackageFile/Parser/v1.php';
//require_once 'PEAR/Registry.php';
//$a = new PEAR_PackageFile_Parser_v1;
//$r = new PEAR_Registry('C:\Program Files\php\pear');
//$a->setRegistry($r);
//$p = &$a->parse(file_get_contents('C:\devel\pear_with_channels\package-PEAR.xml'), PEAR_VALIDATE_NORMAL,
////$p = &$a->parse(file_get_contents('C:\devel\chiara\phpdoc\package.xml'), PEAR_VALIDATE_NORMAL,
//    'C:\devel\pear_with_channels\package-PEAR.xml');
//$g = &$p->getDefaultGenerator();
//$v2 = &$g->toV2();
//$g = &$v2->getDefaultGenerator();
//echo $g->toXml();
//?>