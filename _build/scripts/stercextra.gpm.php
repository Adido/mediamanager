<?php
/**
 * @package mediamanager
 * @subpackage build
 */

use MODX\Revolution\modSystemSetting;
use MODX\Revolution\Transport\modTransportPackage;
use xPDO\Transport\xPDOTransport;

return new class() {
    private $modx;
    private $action;

    public function __invoke(&$modx, $action, $options = [], $object = null)
    {
        $this->modx =& $modx;
        $this->action = $action;

        $package = 'mediamanager';
        $url     = 'https://extras.sterc.nl/api/v1/packagedata';

        $c = $modx->newQuery(modTransportPackage::class);
        $c->where(
            [
                'workspace' => 1,
                "(SELECT
                    `signature`
                    FROM {$modx->getTableName(modTransportPackage::class)} AS `latestPackage`
                    WHERE `latestPackage`.`package_name` = `modTransportPackage`.`package_name`
                    ORDER BY
                        `latestPackage`.`version_major` DESC,
                        `latestPackage`.`version_minor` DESC,
                        `latestPackage`.`version_patch` DESC,
                        IF(`release` = '' OR `release` = 'ga' OR `release` = 'pl','z',`release`) DESC,
                        `latestPackage`.`release_index` DESC
                        LIMIT 1,1) = `modTransportPackage`.`signature`",
            ]
        );
        $c->where(
            [
                [
                    'modTransportPackage.package_name' => strtolower($package)
                ],
                'installed:IS NOT' => null
            ]
        );
        $c->limit(1);

        /** @var modTransportPackage $oldPackage */
        $oldPackage = $modx->getObject(modTransportPackage::class, $c);

        $oldVersion = '';
        if ($oldPackage) {
            $oldVersion = $oldPackage->get('version_major') . '.' . $oldPackage->get('version_minor');
            $oldVersion .= '.' . $oldPackage->get('version_patch');
            $oldVersion .= '-' . $oldPackage->get('release');
        }

        $version = '';
        if (!empty($options['topic'])) {
            $topic     = trim($options['topic'], '/');
            $topic     = explode('/', $topic);
            $signature = end($topic);
            $version   = str_replace(strtolower($package) . '-', '', $signature);
        }

        $userNameObj = $modx->getObject(
            modSystemSetting::class,
            ['key' => strtolower($package) . '.user_name']
        );
        $userName = ($userNameObj) ? $userNameObj->get('value') : '';

        $userEmailObj = $modx->getObject(
            modSystemSetting::class,
            ['key' => strtolower($package) . '.user_email']
        );
        $userEmail = ($userEmailObj) ? $userEmailObj->get('value') : '';

        $modxVersionObj = $modx->getObject(modSystemSetting::class, ['key' => 'settings_version']);
        $modxVersion    = ($modxVersionObj) ? $modxVersionObj->get('value') : '';
        $managerLang    = $modx->getOption('manager_language');

        $actionName = '';
        switch ($this->action) {
            case xPDOTransport::ACTION_INSTALL:
                $actionName = 'install';
                break;
            case xPDOTransport::ACTION_UPGRADE:
                $actionName = 'upgrade';
                break;
            case xPDOTransport::ACTION_UNINSTALL:
                $actionName = 'uninstall';

                $version          = $oldVersion;
                $setupOptionsPath = explode('/', $options['setup-options'] ?? '');
                $signature        = $setupOptionsPath[0];
                $oldVersion       = str_replace(strtolower($package) . '-', '', $signature);

                break;
        }

        $params = [
            'name'                 => $options['namespace'] ?? $package,
            'url'                  => $_SERVER['SERVER_NAME'] ?? '',
            'user_name'            => $userName,
            'user_email'           => $userEmail,
            'php_version'          => phpversion(),
            'modx_version'         => $modxVersion,
            'manager_lang'         => $managerLang,
            'installation_type'    => $actionName,
            'package_version_from' => $oldVersion,
            'package_version'      => $version,
            'date'                 => time()
        ];

        /**
         * Curl POST.
         */
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: STERC-A64XHC7PNY8G61L79E']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);

        curl_exec($curl);
        curl_getinfo($curl);
        curl_error($curl);
        curl_close($curl);

        return true;
    }
};
