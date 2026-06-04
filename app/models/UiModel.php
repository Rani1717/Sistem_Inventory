<?php
class UiModel
{
    private const DEFAULT_DISPLAY_DIVISION = 'SEMUA DIVISI';

    public function getAll(string $page, array $filters = []): array
    {
        $data = $this->getBaseData();

        $pdo = Database::getConnection();
        if (!$pdo instanceof PDO) {
            return $data;
        }

        try {
            $context = $this->resolveContext($pdo, $filters);
            $this->ensureInventoryMetaTables($pdo);
            $data['current_display_division'] = $context['display_division'];
            $data['current_division_code'] = $context['division_code'];
            $data['brand'] = 'SISTEM INFORMASI SPMT IT ASSET MANAGEMENT';
            $data['updated'] = $this->fetchLastUpdatedForContext($pdo, $context);
            $data['user_email'] = $context['current_user_info']['email'] ?? $data['user_email'];
            $data['summary_specs'] = $this->buildSummarySpecs($context['pc_row']) ?: $data['summary_specs'];
            $data['dashboard_cards'] = $this->buildMajorityDashboardCards($pdo) ?: $this->buildDashboardCards($data['summary_specs']);
            $data['dashboard_recap'] = $this->buildDashboardRecap($pdo);
            $data['inventory_rows'] = $this->buildInventoryRows($pdo, $context);
            $data['current_pc_row'] = $context['pc_row'];
            $data['current_page_key'] = $this->buildPageKeyFromPcRow((array) ($context['pc_row'] ?? []));
            $data['raw_other_items'] = $this->fetchOtherItemsForUser($pdo, (string) ($context['inventory_db'] ?? ''), (string) ($context['current_user_name'] ?? ''), (array) ($context['pc_row'] ?? []));
            $data['division_meta'] = $context['division'] ?? [];
            $data['pagination'] = $this->buildUserPagination($context);
            $data['category_cards'] = $this->buildCategoryCards($pdo, $page === 'data-inventaris-subreg' ? 'SUBREG' : null);
            $data['division_nav'] = $this->buildDivisionNav($pdo, $context['division'] ?? null, (string) ($context['division_code'] ?? ''));
            $data['complaint_rows'] = $this->fetchComplaintRows($pdo, $filters);
            $data['it_support_notifications'] = $this->fetchItSupportNotifications($pdo);
            $data['complaint_history_map'] = $this->fetchComplaintHistoryMap($pdo, $data['complaint_rows']);
            $data['complaint_filters'] = $this->buildComplaintFilters($pdo, $filters);
            $data['it_handler_options'] = $this->fetchItHandlerOptions($pdo);
            $data['complaint_chart'] = $this->buildComplaintChart($pdo, $data['complaint_chart']);
            $flowFilters = $page === 'dashboard' ? [] : $filters;
            $data['inventory_flow'] = $this->buildInventoryFlow($pdo, $flowFilters);
            $data['cctv_breakdown'] = $this->buildCctvBreakdown($pdo, $data['cctv_breakdown']);
            $data['log_rows'] = $this->fetchLogRows($pdo, $filters);
            $data['log_filters'] = $this->buildLogFilters($pdo, $filters);
        } catch (Throwable $e) {
            $data['category_cards'] = $this->buildCategoryCardsSafe($pdo, $data['category_cards']);
        }

        return $data;
    }

    private function getBaseData(): array
    {
        return [
            'brand' => 'SISTEM INFORMASI SPMT IT ASSET MANAGEMENT',
            'date' => $this->formatIndonesianDate(new DateTimeImmutable()),
            'time' => $this->formatIndonesianTime(new DateTimeImmutable()),
            'updated' => strtoupper((new DateTimeImmutable())->format('d F Y H:i:s')),
            'user_email' => 'admin.spmt@pelindo.local',
            'menus' => $this->buildMenus(),
            'dashboard_cards' => [
                ['title' => 'SYSTEM OS', 'value' => 'WINDOWS 10 PRO', 'value_class' => 'ok', 'status' => '^ Aman'],
                ['title' => 'MS OFFICE', 'value' => 'UNLICENSED', 'value_class' => 'bad', 'status' => '^ Perlu Update'],
                ['title' => 'PROCESSOR', 'value' => 'CORE I5', 'value_class' => 'ok', 'status' => '^ Aman'],
                ['title' => 'RAM/HARDDISK', 'value' => '12 GB/500 GB', 'value_class' => 'bad', 'status' => '^ Perlu Update'],
            ],
            'cctv_breakdown' => [
                ['label' => 'SAMUDERA', 'value' => 1, 'color' => '#6FCF97'],
                ['label' => 'C.CAIR', 'value' => 2, 'color' => '#5B8DEF'],
                ['label' => 'PELDAM', 'value' => 6, 'color' => '#F2A541'],
                ['label' => 'NUSANTARA', 'value' => 4, 'color' => '#34B3D8'],
                ['label' => 'RORO', 'value' => 9, 'color' => '#F58B82'],
                ['label' => 'TP', 'value' => 6, 'color' => '#7D72F8'],
                ['label' => 'POS GATE 1', 'value' => 0, 'color' => '#3AA0FF'],
                ['label' => 'POS GATE IV', 'value' => 0, 'color' => '#6D5BD0'],
            ],
            'complaint_chart' => [
                'labels' => ['IT', 'TEKNIK', 'RENDAL', 'SDM', 'RUANG RAPAT'],
                'series' => [
                    ['label' => 'MAR', 'color' => '#41B8D5', 'data' => [2, 10, 2, 9, 3]],
                    ['label' => 'APR', 'color' => '#F3A43B', 'data' => [3, 4, 10, 7, 0]],
                    ['label' => 'MAY', 'color' => '#4C7BE8', 'data' => [3, 5, 4, 4, 11]],
                ],
            ],
            'inventory_flow' => [
                'labels' => ['BARANG MASUK', 'BARANG KELUAR'],
                'data' => [20, 5],
                'month' => strtoupper($this->monthName((int) (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('n'))),
                'year' => (int) (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y'),
            ],
            'category_cards' => [],
            'summary_specs' => [
                'computer_name' => '-',
                'user' => '-',
                'processor' => '-',
                'ram' => '-',
                'harddisk' => '-',
                'ip' => '-',
                'os' => '-',
                'license' => '-',
                'office' => '-',
                'office_license' => '-',
            ],
            'inventory_rows' => [],
            'complaint_rows' => [
                ['datetime' => "12 Maret 2026\n13:04:54", 'email' => "xyz@g\nmail.\ncom", 'name' => 'JOKO', 'division' => 'RENDAL', 'item' => 'PC', 'location' => 'RENDAL', 'description' => 'Lemot dan susah dipakai', 'doc_image' => 'images/complaint-1.png', 'status' => 'DONE', 'status_class' => 'good'],
                ['datetime' => "21 Februari 2026\n14:04:34", 'email' => "xyz@g\nmail.\ncom", 'name' => 'ANA S', 'division' => 'TEKNIK', 'item' => 'PRINTER', 'location' => 'TEKNIK', 'description' => 'Tidak bisa print', 'doc_image' => 'images/complaint-2.png', 'status' => 'NOT YET', 'status_class' => 'bad'],
            ],
            'complaint_filters' => [
                'status' => '',
                'division' => '',
                'search' => '',
                'date_from' => '',
                'date_to' => '',
                'division_options' => [],
                'total' => 0,
            ],
            'complaint_history_map' => [],
            'it_support_notifications' => ['count' => 0, 'items' => []],
            'it_handler_options' => [],
            'log_rows' => [
                ['no' => '1.', 'date' => '22/03/2026', 'item' => 'PC DELL OPTIPLEX 3070', 'status' => 'MASUK', 'status_class' => 'in'],
                ['no' => '2.', 'date' => '20/02/2026', 'item' => 'MOUSE LOGITECH', 'status' => 'MASUK', 'status_class' => 'in'],
            ],
            'report_cards' => [
                ['title' => "LAPORAN DATA\nINVENTARIS", 'pdf' => 'EXPORT PDF', 'excel' => 'EXPORT EXCEL'],
                ['title' => "LAPORAN\nKELUHAN", 'pdf' => 'EXPORT PDF', 'excel' => 'EXPORT EXCEL'],
                ['title' => "LAPORAN LOG\nBARANG", 'pdf' => 'EXPORT PDF', 'excel' => 'EXPORT EXCEL'],
            ],
            'page_titles' => [
                'splash' => 'Splash Screen',
                'login' => 'Login',
                'it-support-1' => 'Formulir IT Support Request',
                'it-support-2' => 'Formulir IT Support Request - Lampiran',
                'inventory-pc' => 'Form PC',
                'inventory-other' => 'Form Perangkat Lain',
                'dashboard' => 'Dashboard',
                'data-inventaris' => 'Data Inventaris',
                'data-inventaris-subreg' => 'Data Inventaris Subreg',
                'inventaris-detail' => 'Detail Inventaris',
                'data-keluhan' => 'Data Keluhan',
                'log-barang' => 'Log Barang',
                'routine-monitoring' => 'Routine Monitoring',
                'laporan' => 'Laporan',
                'account-settings' => 'Setting Akun',
                'user-management' => 'Kelola User',
            ],
            'current_display_division' => self::DEFAULT_DISPLAY_DIVISION,
            'current_division_code' => '',
            'division_nav' => ['prev' => null, 'next' => null],
            'current_pc_row' => [],
            'current_page_key' => '',
            'raw_other_items' => [],
            'division_meta' => [],
        ];
    }

    private function buildMenus(): array
    {
        if (!AuthController::check()) {
            return [];
        }

        $menus = [
            [
                'label' => 'INVENTARIS BARU',
                'icon' => 'fa-regular fa-square-plus',
                'route' => 'inventory-pc',
                'match' => ['inventory-pc', 'inventory-other'],
                'variant' => 'pill',
            ],
            [
                'label' => 'DASHBOARD',
                'icon' => 'fa-solid fa-house',
                'route' => 'dashboard',
                'match' => ['dashboard'],
                'variant' => 'nav',
            ],
            [
                'label' => 'DATA INVENTARIS',
                'icon' => 'fa-solid fa-database',
                'route' => 'data-inventaris',
                'match' => ['data-inventaris', 'inventaris-detail'],
                'variant' => 'nav',
            ],
            [
                'label' => 'IT SUPPORT ISSUE',
                'icon' => 'fa-solid fa-user-group',
                'route' => 'data-keluhan',
                'match' => ['data-keluhan'],
                'variant' => 'nav',
            ],
            [
                'label' => 'LOG BARANG',
                'icon' => 'fa-solid fa-right-from-bracket',
                'route' => 'log-barang',
                'match' => ['log-barang'],
                'variant' => 'nav',
            ],
            [
                'label' => 'ROUTINE MONITORING',
                'icon' => 'fa-solid fa-clipboard-check',
                'route' => 'routine-monitoring',
                'match' => ['routine-monitoring'],
                'variant' => 'nav',
            ],
            [
                'label' => 'KELOLA USER',
                'icon' => 'fa-solid fa-user-shield',
                'route' => 'user-management',
                'match' => ['user-management'],
                'variant' => 'nav',
                'admin_only' => true,
            ],
            [
                'label' => 'LAPORAN',
                'icon' => 'fa-regular fa-file-lines',
                'route' => 'laporan',
                'match' => ['laporan'],
                'variant' => 'nav',
            ],
        ];

        return array_values(array_filter($menus, static function (array $menu): bool {
            return AuthController::canAccessPage((string) ($menu['route'] ?? ''));
        }));
    }

    private function resolveContext(PDO $pdo, array $filters): array
{
    $divisionCode = trim((string) ($filters['division_code'] ?? ''));
    $requestedUser = trim((string) ($filters['user'] ?? ''));
    $requestedEmail = trim((string) ($filters['email'] ?? ''));
    $requestedDisplayDivision = trim((string) ($filters['display_division'] ?? ''));
    $requestedPage = max(1, (int) ($filters['user_page'] ?? 1));

    $division = null;

    if ($divisionCode !== '') {
        $division = $this->fetchDivisionByCode($pdo, $divisionCode);
    }

    if (!$division) {
        $division = $this->fetchDivisionFromSession($pdo);
    }

    if (!$division) {
        $division = $this->fetchFirstActiveDivision($pdo);
    }

    if (!$division) {
        throw new RuntimeException('Division metadata not found');
    }

    $inventoryDb = (string) ($division['inventory_db_name'] ?? '');
    if (!$this->isSafeIdentifier($inventoryDb)) {
        throw new RuntimeException('Unsafe inventory db name');
    }
    $this->ensurePcDetailOrderingSchema($pdo, $inventoryDb);

    if ($requestedEmail !== '' && $requestedUser === '') {
        $userInfoByEmail = $this->fetchUserByEmail($pdo, $requestedEmail);
        if ($userInfoByEmail && !empty($userInfoByEmail['nama_lengkap'])) {
            $requestedUser = (string) $userInfoByEmail['nama_lengkap'];
        }
    }

    $userPages = $this->fetchDivisionUserPages($pdo, $inventoryDb);
    $totalPages = max(1, count($userPages));
    $currentPage = min($requestedPage, $totalPages);

    $pcRow = null;

    if ($requestedUser !== '') {
        $pcRow = $this->fetchPcRow($pdo, $inventoryDb, $requestedUser);
    } elseif (!empty($userPages)) {
        $pageMeta = $userPages[$currentPage - 1] ?? null;
        if ($pageMeta) {
            $pcRow = $this->fetchPcRowByPageMeta($pdo, $inventoryDb, $pageMeta);
        }
    }

    if (!$pcRow) {
        $pcRow = $this->fetchPreferredPcRow(
            $pdo,
            $inventoryDb,
            (string) ($division['division_label'] ?? '')
        );
    }

    if (!$pcRow) {
        $pcRow = $this->fetchFirstPcRow($pdo, $inventoryDb);
    }

    if (!$pcRow) {
        throw new RuntimeException('No inventory pc row found');
    }

    $currentUserName = trim((string) ($pcRow['user'] ?? ''));
    $currentUserInfo = $this->fetchUserByName($pdo, $currentUserName);

    if (!$currentUserInfo && $requestedEmail !== '') {
        $currentUserInfo = $this->fetchUserByEmail($pdo, $requestedEmail);
    }

    $currentPage = $this->resolveUserPageFromPcRow($userPages, $pcRow, $currentPage);

    $displayDivision = $requestedDisplayDivision !== ''
        ? strtoupper($requestedDisplayDivision)
        : $this->normalizeDivisionLabel(
            (string) ($division['division_label'] ?? ''),
            (string) ($pcRow['unit_kerja'] ?? '')
        );

    return [
        'division' => $division,
        'inventory_db' => $inventoryDb,
        'pc_row' => $pcRow,
        'division_code' => (string) ($division['division_code'] ?? ''),
        'current_user_name' => $currentUserName,
        'current_user_info' => $currentUserInfo,
        'display_division' => $displayDivision,
        'requested_user' => $requestedUser,
        'requested_email' => $requestedEmail,
        'user_pages' => $userPages,
        'current_user_page' => $currentPage,
        'total_user_pages' => $totalPages,
    ];
    }

    private function fetchDivisionByCode(PDO $pdo, string $divisionCode): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM master_divisi WHERE division_code = :division_code LIMIT 1');
        $stmt->execute(['division_code' => $divisionCode]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function fetchDivisionById(PDO $pdo, int $divisionId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM master_divisi WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $divisionId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function fetchDivisionFromSession(PDO $pdo): ?array
    {
        $divisionId = (int) ($_SESSION['auth']['default_divisi_id'] ?? 0);
        return $divisionId > 0 ? $this->fetchDivisionById($pdo, $divisionId) : null;
    }

    private function fetchFirstActiveDivision(PDO $pdo): ?array
    {
    $stmt = $pdo->query('SELECT * FROM master_divisi WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
    $row = $stmt ? $stmt->fetch() : null;

    return $row ?: null;
    }

    private function fetchPreferredPcRow(PDO $pdo, string $inventoryDb, string $divisionLabel): ?array
    {
        $divisionLabel = strtoupper(trim($divisionLabel));

        $preferredUnit = '';
        if (strpos($divisionLabel, 'TEKNIK') !== false && strpos($divisionLabel, 'IT') !== false) {
            $preferredUnit = 'IT';
        } elseif (strpos($divisionLabel, 'TEKNIK') !== false) {
            $preferredUnit = 'TEKNIK';
        } elseif ($divisionLabel !== '') {
            $preferredUnit = $divisionLabel;
        }

        if ($preferredUnit !== '') {
            $sql = sprintf(
                'SELECT * FROM `%s`.pc
                 WHERE COALESCE(`unit_kerja`, "") LIKE :unit
                 ORDER BY COALESCE(NULLIF(`user`, ""), `computer_name`) ASC
                 LIMIT 1',
                $inventoryDb
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['unit' => '%' . $preferredUnit . '%']);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    private function buildSummarySpecs(array $pcRow): array
    {
        return [
            'computer_name' => $this->displayValue($pcRow['computer_name'] ?? null),
            'user' => $this->displayValue($pcRow['user'] ?? null),
            'processor' => $this->displayValue($pcRow['processor'] ?? null),
            'ram' => $this->displayValue($pcRow['ram'] ?? null),
            'harddisk' => $this->displayValue($pcRow['harddisk'] ?? $pcRow['kapasitas_harddisk'] ?? null),
            'ip' => $this->displayValue($pcRow['ip'] ?? $pcRow['ip_address'] ?? null),
            'os' => $this->displayValue($pcRow['os'] ?? $pcRow['sistem_operasi'] ?? null),
            'license' => $this->displayValue($pcRow['license'] ?? $pcRow['licensed_windows'] ?? null),
            'office' => $this->displayValue($pcRow['office'] ?? $pcRow['microsoft_office'] ?? null),
            'office_license' => $this->displayValue($pcRow['office_license'] ?? $pcRow['licensed_office'] ?? null),
            'jenis_perangkat' => 'PC',
            'merk_perangkat' => $this->displayValue($pcRow['merk_perangkat'] ?? (((string) ($pcRow['jenis_perangkat'] ?? '')) !== 'PC' ? ($pcRow['jenis_perangkat'] ?? null) : null)),
        ];
    }

    private function buildDashboardRecap(PDO $pdo): array
    {
        $empty = [
            'SYSTEM OS' => ['title' => 'SYSTEM OS', 'total' => 0, 'safe_total' => 0, 'bad_total' => 0, 'groups' => [], 'top_values' => []],
            'MS OFFICE' => ['title' => 'MS OFFICE', 'total' => 0, 'licensed_total' => 0, 'unlicensed_total' => 0, 'other_total' => 0, 'groups' => [], 'top_values' => []],
            'PROCESSOR' => ['title' => 'PROCESSOR', 'total' => 0, 'safe_total' => 0, 'bad_total' => 0, 'groups' => [], 'top_values' => []],
            'RAM/HARDDISK' => ['title' => 'RAM/HARDDISK', 'total' => 0, 'safe_total' => 0, 'bad_total' => 0, 'groups' => [], 'top_values' => []],
        ];

        try {
            $stmt = $pdo->query('SELECT division_label, division_code, inventory_db_name FROM master_divisi WHERE is_active = 1 ORDER BY id ASC');
            $divisions = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            if (!$divisions) {
                return $empty;
            }

            $valueCounts = [
                'SYSTEM OS' => [],
                'MS OFFICE' => [],
                'PROCESSOR' => [],
                'RAM/HARDDISK' => [],
            ];
            $divisionCounts = [];
            $total = 0;
            $safe = ['SYSTEM OS' => 0, 'PROCESSOR' => 0, 'RAM/HARDDISK' => 0];
            $bad = ['SYSTEM OS' => 0, 'PROCESSOR' => 0, 'RAM/HARDDISK' => 0];
            $licensed = 0;
            $unlicensed = 0;
            $officeOther = 0;

            foreach ($divisions as $division) {
                $db = (string) ($division['inventory_db_name'] ?? '');
                if (!$this->isSafeIdentifier($db)) {
                    continue;
                }
                $divisionLabel = trim((string) (($division['division_label'] ?? '') ?: ($division['division_code'] ?? $db)));
                try {
                    $sql = sprintf('SELECT sistem_operasi, licensed_office, microsoft_office, processor, ram, kapasitas_harddisk FROM `%s`.pc', $db);
                    $rows = $pdo->query($sql);
                    if (!$rows) {
                        continue;
                    }
                    foreach ($rows as $row) {
                        $total++;
                        $os = strtoupper($this->majorityNormalize($row['sistem_operasi'] ?? ''));
                        $officeLicense = strtoupper($this->majorityNormalize($row['licensed_office'] ?? ''));
                        $officeName = strtoupper($this->majorityNormalize($row['microsoft_office'] ?? ''));
                        $officeValue = $officeLicense !== '' ? $officeLicense : ($officeName !== '' ? $officeName : '-');
                        $processor = strtoupper($this->majorityNormalize($row['processor'] ?? ''));
                        $ram = strtoupper($this->majorityNormalize($row['ram'] ?? ''));
                        $disk = strtoupper($this->majorityNormalize($row['kapasitas_harddisk'] ?? ''));
                        $ramDisk = trim($ram . '/' . $disk, '/');
                        if ($ramDisk === '') {
                            $ramDisk = '-';
                        }

                        $this->increaseDashboardRecapCount($valueCounts['SYSTEM OS'], $os !== '' ? $os : '-');
                        $this->increaseDashboardRecapCount($valueCounts['MS OFFICE'], $officeValue);
                        $this->increaseDashboardRecapCount($valueCounts['PROCESSOR'], $processor !== '' ? $processor : '-');
                        $this->increaseDashboardRecapCount($valueCounts['RAM/HARDDISK'], $ramDisk);

                        if ($this->dashboardSpecIsSafe('SYSTEM OS', $os)) { $safe['SYSTEM OS']++; } else { $bad['SYSTEM OS']++; }
                        if ($this->dashboardSpecIsSafe('PROCESSOR', $processor)) { $safe['PROCESSOR']++; } else { $bad['PROCESSOR']++; }
                        if ($this->dashboardSpecIsSafe('RAM/HARDDISK', $ramDisk)) { $safe['RAM/HARDDISK']++; } else { $bad['RAM/HARDDISK']++; }

                        $officeNorm = $this->normalizeSpecText($officeValue);
                        if (strpos($officeNorm, 'UNLICENSED') !== false || strpos($officeNorm, 'TIDAKLICENSE') !== false || strpos($officeNorm, 'NONLICENSE') !== false) {
                            $unlicensed++;
                            $officeStatus = 'UNLICENSED';
                        } elseif (strpos($officeNorm, 'LICENSED') !== false || strpos($officeNorm, 'AKTIF') !== false) {
                            $licensed++;
                            $officeStatus = 'LICENSED';
                        } else {
                            $officeOther++;
                            $officeStatus = 'LAINNYA';
                        }

                        if ($divisionLabel !== '') {
                            if (!isset($divisionCounts[$divisionLabel])) {
                                $divisionCounts[$divisionLabel] = ['division' => $divisionLabel, 'total' => 0, 'licensed' => 0, 'unlicensed' => 0, 'other' => 0];
                            }
                            $divisionCounts[$divisionLabel]['total']++;
                            if ($officeStatus === 'LICENSED') { $divisionCounts[$divisionLabel]['licensed']++; }
                            elseif ($officeStatus === 'UNLICENSED') { $divisionCounts[$divisionLabel]['unlicensed']++; }
                            else { $divisionCounts[$divisionLabel]['other']++; }
                        }
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }

            $empty['SYSTEM OS']['total'] = $total;
            $empty['SYSTEM OS']['safe_total'] = $safe['SYSTEM OS'];
            $empty['SYSTEM OS']['bad_total'] = $bad['SYSTEM OS'];
            $empty['SYSTEM OS']['groups'] = [
                ['label' => 'Aman / Windows 11 Pro', 'total' => $safe['SYSTEM OS'], 'type' => 'ok'],
                ['label' => 'Perlu Update', 'total' => $bad['SYSTEM OS'], 'type' => 'bad'],
            ];
            $empty['SYSTEM OS']['top_values'] = $this->dashboardRecapTopValues($valueCounts['SYSTEM OS']);

            $empty['MS OFFICE']['total'] = $total;
            $empty['MS OFFICE']['licensed_total'] = $licensed;
            $empty['MS OFFICE']['unlicensed_total'] = $unlicensed;
            $empty['MS OFFICE']['other_total'] = $officeOther;
            $empty['MS OFFICE']['groups'] = [
                ['label' => 'Licensed', 'total' => $licensed, 'type' => 'ok'],
                ['label' => 'Unlicensed', 'total' => $unlicensed, 'type' => 'bad'],
                ['label' => 'Lainnya / kosong', 'total' => $officeOther, 'type' => 'neutral'],
            ];
            $empty['MS OFFICE']['top_values'] = $this->dashboardRecapTopValues($valueCounts['MS OFFICE']);
            $empty['MS OFFICE']['division_rows'] = array_values($divisionCounts);

            $empty['PROCESSOR']['total'] = $total;
            $empty['PROCESSOR']['safe_total'] = $safe['PROCESSOR'];
            $empty['PROCESSOR']['bad_total'] = $bad['PROCESSOR'];
            $empty['PROCESSOR']['groups'] = [
                ['label' => 'Aman / i5-i7', 'total' => $safe['PROCESSOR'], 'type' => 'ok'],
                ['label' => 'Perlu Update', 'total' => $bad['PROCESSOR'], 'type' => 'bad'],
            ];
            $empty['PROCESSOR']['top_values'] = $this->dashboardRecapTopValues($valueCounts['PROCESSOR']);

            $empty['RAM/HARDDISK']['total'] = $total;
            $empty['RAM/HARDDISK']['safe_total'] = $safe['RAM/HARDDISK'];
            $empty['RAM/HARDDISK']['bad_total'] = $bad['RAM/HARDDISK'];
            $empty['RAM/HARDDISK']['groups'] = [
                ['label' => 'Aman', 'total' => $safe['RAM/HARDDISK'], 'type' => 'ok'],
                ['label' => 'Perlu Upgrade', 'total' => $bad['RAM/HARDDISK'], 'type' => 'bad'],
            ];
            $empty['RAM/HARDDISK']['top_values'] = $this->dashboardRecapTopValues($valueCounts['RAM/HARDDISK']);

            return $empty;
        } catch (Throwable $e) {
            return $empty;
        }
    }

    private function increaseDashboardRecapCount(array &$counts, string $value): void
    {
        $value = trim($value) !== '' ? trim($value) : '-';
        $key = strtoupper($value);
        if (!isset($counts[$key])) {
            $counts[$key] = ['label' => $value, 'total' => 0];
        }
        $counts[$key]['total']++;
    }

    private function dashboardRecapTopValues(array $counts, int $limit = 8): array
    {
        if (!$counts) {
            return [];
        }
        uasort($counts, static function (array $a, array $b): int {
            $totalCompare = ((int) ($b['total'] ?? 0)) <=> ((int) ($a['total'] ?? 0));
            if ($totalCompare !== 0) {
                return $totalCompare;
            }
            return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });
        return array_slice(array_values($counts), 0, $limit);
    }

    private function buildMajorityDashboardCards(PDO $pdo): array
    {
        $defaults = $this->getBaseData()['dashboard_cards'];
        try {
            $stmt = $pdo->query('SELECT inventory_db_name FROM master_divisi WHERE is_active = 1 ORDER BY id ASC');
            $divisions = $stmt ? $stmt->fetchAll() : [];
            if (!$divisions) {
                return [];
            }

            $counts = ['os' => [], 'office' => [], 'processor' => [], 'ramdisk' => []];
            foreach ($divisions as $division) {
                $db = (string) ($division['inventory_db_name'] ?? '');
                if (!$this->isSafeIdentifier($db)) {
                    continue;
                }
                try {
                    $sql = sprintf('SELECT sistem_operasi, licensed_office, microsoft_office, processor, ram, kapasitas_harddisk FROM `%s`.pc', $db);
                    $rows = $pdo->query($sql);
                    if (!$rows) {
                        continue;
                    }
                    foreach ($rows as $row) {
                        $os = $this->majorityNormalize($row['sistem_operasi'] ?? '');
                        $officeLicense = $this->majorityNormalize($row['licensed_office'] ?? '');
                        $officeName = $this->majorityNormalize($row['microsoft_office'] ?? '');
                        $processor = $this->majorityNormalize($row['processor'] ?? '');
                        $ramDisk = trim($this->majorityNormalize($row['ram'] ?? '') . '/' . $this->majorityNormalize($row['kapasitas_harddisk'] ?? ''), '/');
                        $this->increaseMajorityCount($counts['os'], $os);
                        $this->increaseMajorityCount($counts['office'], $officeLicense !== '' ? $officeLicense : $officeName);
                        $this->increaseMajorityCount($counts['processor'], $processor);
                        $this->increaseMajorityCount($counts['ramdisk'], $ramDisk);
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }

            $os = $this->majorityPick($counts['os']);
            $office = $this->majorityPick($counts['office']);
            $processor = $this->majorityPick($counts['processor']);
            $ramDisk = $this->majorityPick($counts['ramdisk']);
            if ($os === '' && $office === '' && $processor === '' && $ramDisk === '') {
                return [];
            }

            $osValue = strtoupper($os !== '' ? $os : $defaults[0]['value']);
            $officeValue = strtoupper($office !== '' ? $office : $defaults[1]['value']);
            $processorValue = strtoupper($processor !== '' ? $processor : $defaults[2]['value']);
            $ramDiskValue = strtoupper($ramDisk !== '' ? $ramDisk : $defaults[3]['value']);

            return [
                ['title' => 'SYSTEM OS', 'value' => $osValue, 'value_class' => $this->dashboardSpecIsSafe('SYSTEM OS', $osValue) ? 'ok' : 'bad', 'status' => $this->dashboardSpecStatus('SYSTEM OS', $osValue)],
                ['title' => 'MS OFFICE', 'value' => $officeValue, 'value_class' => $this->dashboardSpecIsSafe('MS OFFICE', $officeValue) ? 'ok' : 'bad', 'status' => $this->dashboardSpecStatus('MS OFFICE', $officeValue)],
                ['title' => 'PROCESSOR', 'value' => $processorValue, 'value_class' => $this->dashboardSpecIsSafe('PROCESSOR', $processorValue) ? 'ok' : 'bad', 'status' => $this->dashboardSpecStatus('PROCESSOR', $processorValue)],
                ['title' => 'RAM/HARDDISK', 'value' => $ramDiskValue, 'value_class' => $this->dashboardSpecIsSafe('RAM/HARDDISK', $ramDiskValue) ? 'ok' : 'bad', 'status' => $this->dashboardSpecStatus('RAM/HARDDISK', $ramDiskValue)],
            ];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function majorityNormalize($value): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $value));
        return $text === '-' ? '' : $text;
    }

    private function dashboardSpecStatus(string $title, string $value): string
    {
        return $this->dashboardSpecIsSafe($title, $value) ? '^aman' : '^perlu update';
    }

    private function dashboardSpecIsSafe(string $title, string $value): bool
    {
        $normalized = $this->normalizeSpecText($value);
        switch (strtoupper($title)) {
            case 'SYSTEM OS':
                return strpos($normalized, 'WINDOWS11PRO') !== false;
            case 'MS OFFICE':
                return strpos($normalized, 'UNLICENSED') === false && strpos($normalized, 'LICENSED') !== false;
            case 'PROCESSOR':
                return strpos($normalized, 'COREI5') !== false || strpos($normalized, 'COREI7') !== false || strpos($normalized, 'I5') !== false || strpos($normalized, 'I7') !== false;
            case 'RAM/HARDDISK':
                foreach (['8GB/256GB', '8GB/500GB', '8GB/512GB', '8GB/1TB', '16GB/256GB', '16GB/512GB', '16GB/1TB'] as $safeRamDisk) {
                    if (strpos($normalized, $safeRamDisk) !== false) {
                        return true;
                    }
                }
                return false;
            default:
                return false;
        }
    }

    private function normalizeSpecText(string $value): string
    {
        $text = strtoupper(trim($value));
        $text = str_replace(['\\', ' / ', '/ ', ' /'], '/', $text);
        $text = preg_replace('/\s+/', '', $text);
        return $text ?? '';
    }

    private function increaseMajorityCount(array &$counts, string $value): void
    {
        $value = $this->majorityNormalize($value);
        if ($value === '') {
            return;
        }
        $key = strtoupper($value);
        if (!isset($counts[$key])) {
            $counts[$key] = ['label' => $value, 'total' => 0];
        }
        $counts[$key]['total']++;
    }

    private function majorityPick(array $counts): string
    {
        if (!$counts) {
            return '';
        }
        uasort($counts, static function (array $a, array $b): int {
            $totalCompare = ((int) ($b['total'] ?? 0)) <=> ((int) ($a['total'] ?? 0));
            if ($totalCompare !== 0) {
                return $totalCompare;
            }
            return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });
        $first = reset($counts);
        return is_array($first) ? (string) ($first['label'] ?? '') : '';
    }

    private function buildDashboardCards(array $summarySpecs): array
    {
        $cards = [
            ['title' => 'SYSTEM OS', 'value' => strtoupper((string) ($summarySpecs['os'] ?? '-'))],
            ['title' => 'MS OFFICE', 'value' => strtoupper((string) ($summarySpecs['office_license'] ?? '-'))],
            ['title' => 'PROCESSOR', 'value' => strtoupper((string) ($summarySpecs['processor'] ?? '-'))],
            ['title' => 'RAM/HARDDISK', 'value' => strtoupper((string) ($summarySpecs['ram'] ?? '-') . '/' . (string) ($summarySpecs['harddisk'] ?? '-'))],
        ];

        foreach ($cards as &$card) {
            $card['value_class'] = $this->dashboardSpecIsSafe($card['title'], $card['value']) ? 'ok' : 'bad';
            $card['status'] = $this->dashboardSpecStatus($card['title'], $card['value']);
        }
        unset($card);

        return $cards;
    }
    private function buildCategoryCardsSafe(PDO $pdo, array $fallback): array
    {
        try {
            return $this->buildCategoryCards($pdo, null);
        } catch (Throwable $e) {
            return $fallback;
        }
    }

    private function buildCategoryCards(PDO $pdo, ?string $sheetSourceFilter = null): array
    {
        $stmt = $pdo->query('
            SELECT id, division_code, division_label, division_group_name, sheet_sumber
            FROM master_divisi
            WHERE is_active = 1
            ORDER BY id ASC
        ');
        $rows = $stmt ? $stmt->fetchAll() : [];

        if (!$rows) {
            return $this->defaultCategoryCards();
        }

        $cards = [];
        $hasSubreg = false;
        $sheetSourceFilter = $sheetSourceFilter !== null ? strtoupper(trim($sheetSourceFilter)) : null;

        foreach ($rows as $row) {
            $sheetSource = strtoupper(trim((string) ($row['sheet_sumber'] ?? '')));

            if ($sheetSourceFilter !== null && $sheetSource !== $sheetSourceFilter) {
                continue;
            }

            if ($sheetSourceFilter === null && $sheetSource === 'SUBREG') {
                $hasSubreg = true;
                continue;
            }

            $displayLabel = $this->normalizeDivisionLabel(
                (string) ($row['division_label'] ?? ''),
                ''
            );

            $cards[] = [
                'icon' => $this->mapDivisionIcon(
                    (string) ($row['division_code'] ?? ''),
                    $displayLabel
                ),
                'label' => $displayLabel,
                'sub_label' => $sheetSource === 'SUBREG' ? 'SUBREG' : 'SPMT',
                'route_url' => 'index.php?' . http_build_query([
                    'page' => 'inventaris-detail',
                    'division_code' => (string) ($row['division_code'] ?? ''),
                    'display_division' => $displayLabel,
                ]),
            ];
        }

        if ($sheetSourceFilter === null && $hasSubreg) {
            $cards[] = [
                'icon' => 'fa-solid fa-layer-group',
                'label' => "SUBREG",
                'sub_label' => 'LIHAT DETAIL',
                'route_url' => 'index.php?page=data-inventaris-subreg',
                'is_disabled' => false,
            ];
        }

        return $cards ?: $this->defaultCategoryCards();
    }

    private function buildDivisionNav(PDO $pdo, ?array $currentDivision, string $currentDivisionCode): array
    {
        $sheetSource = strtoupper(trim((string) ($currentDivision['sheet_sumber'] ?? '')));

        if ($sheetSource !== '') {
            $stmt = $pdo->prepare('SELECT division_code, division_label, sheet_sumber FROM master_divisi WHERE is_active = 1 AND UPPER(sheet_sumber) = :sheet_sumber ORDER BY id ASC');
            $stmt->execute(['sheet_sumber' => $sheetSource]);
            $rows = $stmt->fetchAll();
        } else {
            $stmt = $pdo->query('SELECT division_code, division_label, sheet_sumber FROM master_divisi WHERE is_active = 1 ORDER BY id ASC');
            $rows = $stmt ? $stmt->fetchAll() : [];
        }

        if (!$rows) {
            return ['prev' => null, 'next' => null];
        }

        $currentIndex = 0;
        foreach ($rows as $index => $row) {
            if ((string) ($row['division_code'] ?? '') === $currentDivisionCode) {
                $currentIndex = $index;
                break;
            }
        }

        $total = count($rows);
        $prevRow = $rows[($currentIndex - 1 + $total) % $total];
        $nextRow = $rows[($currentIndex + 1) % $total];

        $buildLink = function (array $row): array {
            $displayLabel = $this->normalizeDivisionLabel((string) ($row['division_label'] ?? ''), '');
            return [
                'label' => $displayLabel,
                'href' => 'index.php?' . http_build_query([
                    'page' => 'inventaris-detail',
                    'division_code' => (string) ($row['division_code'] ?? ''),
                    'display_division' => $displayLabel,
                ]),
            ];
        };

        return [
            'prev' => $buildLink($prevRow),
            'next' => $buildLink($nextRow),
        ];
    }

    private function buildInventoryRows(PDO $pdo, array $context): array
    {
        $inventoryDb = (string) ($context['inventory_db'] ?? '');
        $currentUserName = trim((string) ($context['current_user_name'] ?? ''));
        $pcRow = (array) ($context['pc_row'] ?? []);

        return $this->buildInventoryRowsByUser($pdo, $inventoryDb, $currentUserName, $pcRow);
    }

    private function fetchOtherItemsForUser(PDO $pdo, string $inventoryDb, string $currentUserName, array $pcRow): array
    {
        if ($currentUserName !== '') {
            $sql = sprintf(
                'SELECT * FROM `%s`.perangkat_lain WHERE `user` = :user ORDER BY COALESCE(NULLIF(`jenis_perangkat`, ""), `id_inventaris`, `gambar`) ASC',
                $inventoryDb
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user' => $currentUserName]);
            return $stmt->fetchAll() ?: [];
        }

        $identifier = trim((string) ($pcRow['computer_name'] ?? ''));
        if ($identifier === '') {
            return [];
        }

        $sql = sprintf(
            'SELECT * FROM `%s`.perangkat_lain WHERE COALESCE(NULLIF(`user`, ""), `unit_kerja`) = :fallback ORDER BY COALESCE(NULLIF(`jenis_perangkat`, ""), `id_inventaris`, `gambar`) ASC',
            $inventoryDb
        );
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['fallback' => $identifier]);

        return $stmt->fetchAll() ?: [];
    }

    private function buildInventoryRowsByUser(PDO $pdo, string $inventoryDb, string $currentUserName, array $pcRow): array
    {
        $rows = [];

        if (!empty($pcRow)) {
            $rows[] = [
                'no' => 1,
                'image' => $this->normalizeImagePath($pcRow['gambar'] ?? '') ?: 'images/inv-pc.png',
                'id' => $this->displayValue($pcRow['id_inventaris'] ?? null),
                'device' => 'PC',
                'brand' => $this->displayValue($pcRow['merk_perangkat'] ?? (((string) ($pcRow['jenis_perangkat'] ?? '')) !== 'PC' ? ($pcRow['jenis_perangkat'] ?? null) : null)),
                'unit' => $this->displayValue($pcRow['unit_kerja'] ?? null),
                'status' => in_array(strtoupper(trim((string) ($pcRow['status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK'], true) ? strtoupper(trim((string) ($pcRow['status'] ?? 'AKTIF'))) : 'AKTIF',
                'status_class' => $this->mapStatusClass(in_array(strtoupper(trim((string) ($pcRow['status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK'], true) ? strtoupper(trim((string) ($pcRow['status'] ?? 'AKTIF'))) : 'AKTIF'),
                'row_key' => 'pc:' . $this->buildPageKeyFromPcRow($pcRow),
            ];
        }

        $others = $this->fetchOtherItemsForUser($pdo, $inventoryDb, $currentUserName, $pcRow);

        $counter = count($rows) + 1;
        foreach ($others as $row) {
            $status = strtoupper(trim((string) ($row['status'] ?? '')));
            $status = $status !== '' ? $status : 'AKTIF';

            $rows[] = [
                'no' => $counter++,
                'image' => $this->resolveInventoryImage((array) $row),
                'id' => $this->displayValue($row['id_inventaris'] ?? null),
                'device' => $this->displayValue($row['jenis_perangkat'] ?? null),
                'brand' => $this->displayValue($row['merk_perangkat'] ?? $this->extractBrandFromText((string) ($row['jenis_perangkat'] ?? ''))),
                'unit' => $this->displayValue($row['unit_kerja'] ?? null),
                'status' => $status,
                'status_class' => $this->mapStatusClass($status),
                'row_key' => 'other:' . $this->buildOtherItemKey((array) $row),
            ];
        }

        return $rows;
    }

    private function buildInventoryRowsByDivision(PDO $pdo, string $inventoryDb): array
    {
        $rows = [];
        $counter = 1;

        $pcSql = sprintf(
            'SELECT *
             FROM `%s`.pc',
            $inventoryDb
        );
        $pcStmt = $pdo->query($pcSql);

        foreach (($pcStmt ? $pcStmt->fetchAll() : []) as $row) {
            $rows[] = [
                'no' => $counter++,
                'image' => $this->normalizeImagePath($row['gambar'] ?? '') ?: 'images/inv-pc.png',
                'id' => $this->displayValue($row['id_inventaris'] ?? null),
                'device' => 'PC',
                'brand' => $this->displayValue($row['merk_perangkat'] ?? (((string) ($row['jenis_perangkat'] ?? '')) !== 'PC' ? ($row['jenis_perangkat'] ?? null) : null)),
                'unit' => $this->displayValue($row['unit_kerja'] ?? null),
                'status' => in_array(strtoupper(trim((string) ($row['status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK'], true) ? strtoupper(trim((string) ($row['status'] ?? 'AKTIF'))) : 'AKTIF',
                'status_class' => $this->mapStatusClass(in_array(strtoupper(trim((string) ($row['status'] ?? 'AKTIF'))), ['AKTIF', 'RUSAK'], true) ? strtoupper(trim((string) ($row['status'] ?? 'AKTIF'))) : 'AKTIF'),
                'row_key' => 'pc:' . md5(strtolower(trim((string) ($row['id_inventaris'] ?? ''))) . '|' . strtolower(trim((string) ($row['computer_name'] ?? '')))),
            ];
        }

        $otherSql = sprintf(
            'SELECT id_inventaris, jenis_perangkat, merk_perangkat, unit_kerja, gambar, COALESCE(NULLIF(`status`, ""), "AKTIF") AS status
             FROM `%s`.perangkat_lain
             ORDER BY COALESCE(NULLIF(`unit_kerja`, ""), "ZZZ"),
                      COALESCE(NULLIF(`jenis_perangkat`, ""), "ZZZ") ASC',
            $inventoryDb
        );
        $otherStmt = $pdo->query($otherSql);

        foreach (($otherStmt ? $otherStmt->fetchAll() : []) as $row) {
            $status = strtoupper(trim((string) ($row['status'] ?? 'AKTIF')));

            $rows[] = [
                'no' => $counter++,
                'image' => $this->resolveInventoryImage((array) $row),
                'id' => $this->displayValue($row['id_inventaris'] ?? null),
                'device' => $this->displayValue($row['jenis_perangkat'] ?? null),
                'brand' => $this->displayValue($row['merk_perangkat'] ?? $this->extractBrandFromText((string) ($row['jenis_perangkat'] ?? ''))),
                'unit' => $this->displayValue($row['unit_kerja'] ?? null),
                'status' => $status !== '' ? $status : 'AKTIF',
                'status_class' => $this->mapStatusClass($status),
                'row_key' => 'other:' . $this->buildOtherItemKey((array) $row),
            ];
        }

        return $rows;
    }

    private function fetchLastUpdatedForContext(PDO $pdo, array $context): string
    {
        $divisionCode = (string) ($context['division_code'] ?? '');
        $pageKey = $this->buildPageKeyFromPcRow((array) ($context['pc_row'] ?? []));

        try {
            $stmt = $pdo->prepare('SELECT updated_at FROM inventory_update_log WHERE division_code = :division_code AND page_key = :page_key ORDER BY updated_at DESC LIMIT 1');
            $stmt->execute(['division_code' => $divisionCode, 'page_key' => $pageKey]);
            $row = $stmt->fetch();
            if (!empty($row['updated_at'])) {
                return strtoupper($this->formatTimestamp((string) $row['updated_at']));
            }
        } catch (Throwable $e) {
        }

        $inventoryDb = (string) ($context['inventory_db'] ?? '');
        if ($this->isSafeIdentifier($inventoryDb)) {
            try {
                $stmt = $pdo->prepare('SELECT MAX(COALESCE(UPDATE_TIME, CREATE_TIME)) AS last_updated FROM information_schema.tables WHERE table_schema = :schema AND table_name IN ("pc", "perangkat_lain")');
                $stmt->execute(['schema' => $inventoryDb]);
                $row = $stmt->fetch();
                if (!empty($row['last_updated'])) {
                    return strtoupper($this->formatTimestamp((string) $row['last_updated']));
                }
            } catch (Throwable $e) {
            }
        }

        try {
            $stmt = $pdo->query('SELECT MAX(updated_at) AS last_updated FROM users');
            $row = $stmt ? $stmt->fetch() : null;
            if (!empty($row['last_updated'])) {
                return strtoupper($this->formatTimestamp((string) $row['last_updated']));
            }
        } catch (Throwable $e) {
        }

        return strtoupper((new DateTimeImmutable())->format('d F Y H:i:s'));
    }

    private function fetchUserByName(PDO $pdo, string $fullName): ?array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE nama_lengkap = :nama_lengkap LIMIT 1');
        $stmt->execute(['nama_lengkap' => $fullName]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function fetchUserByEmail(PDO $pdo, string $email): ?array
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function ensurePcDetailOrderingSchema(PDO $pdo, string $inventoryDb): void
    {
        if (!$this->isSafeIdentifier($inventoryDb)) {
            return;
        }
        $columns = [
            'status' => "ALTER TABLE `%s`.pc ADD COLUMN `status` VARCHAR(100) NULL DEFAULT 'AKTIF' AFTER `gambar`",
            'inventory_order' => 'ALTER TABLE `%s`.pc ADD COLUMN `inventory_order` BIGINT NULL AFTER `status`',
            'inventory_created_at' => 'ALTER TABLE `%s`.pc ADD COLUMN `inventory_created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER `inventory_order`',
            'inventory_updated_at' => 'ALTER TABLE `%s`.pc ADD COLUMN `inventory_updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `inventory_created_at`',
        ];
        foreach ($columns as $column => $template) {
            try {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = "pc" AND column_name = :column');
                $stmt->execute(['schema' => $inventoryDb, 'column' => $column]);
                if ((int) $stmt->fetchColumn() < 1) {
                    $pdo->exec(sprintf($template, $inventoryDb));
                }
            } catch (Throwable $e) {
            }
        }
    }

    private function pcDetailOrderSql(): string
    {
        return 'CASE WHEN `inventory_order` IS NULL THEN 0 ELSE 1 END ASC, `inventory_order` ASC, COALESCE(NULLIF(`user`, ""), NULLIF(`computer_name`, ""), NULLIF(`id_inventaris`, ""), "") ASC, COALESCE(NULLIF(`computer_name`, ""), "") ASC, COALESCE(NULLIF(`id_inventaris`, ""), "") ASC';
    }

    private function fetchFirstPcRow(PDO $pdo, string $inventoryDb): ?array
    {
        $sql = sprintf('SELECT * FROM `%s`.pc ORDER BY %s LIMIT 1', $inventoryDb, $this->pcDetailOrderSql());
        $stmt = $pdo->query($sql);
        $row = $stmt ? $stmt->fetch() : null;

        return $row ?: null;
    }

    private function fetchPcRow(PDO $pdo, string $inventoryDb, string $userName): ?array
    {
        $sql = sprintf('SELECT * FROM `%s`.pc WHERE `user` = :user LIMIT 1', $inventoryDb);
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user' => $userName]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function fetchPcRowByPageMeta(PDO $pdo, string $inventoryDb, array $pageMeta): ?array
    {
        $pageKey = (string) ($pageMeta['page_key'] ?? '');
        if ($pageKey === '') {
            return null;
        }

        $sql = sprintf('SELECT * FROM `%s`.pc ORDER BY %s', $inventoryDb, $this->pcDetailOrderSql());
        $stmt = $pdo->query($sql);
        foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
            if ($this->buildPageKeyFromPcRow($row) === $pageKey) {
                return $row;
            }
        }

        return null;
    }

    private function fetchDivisionUserPages(PDO $pdo, string $inventoryDb): array
    {
        $sql = sprintf('SELECT * FROM `%s`.pc ORDER BY %s', $inventoryDb, $this->pcDetailOrderSql());
        $stmt = $pdo->query($sql);
        $rows = $stmt ? $stmt->fetchAll() : [];
        if (!$rows) {
            return [];
        }

        $pages = [];
        $seen = [];
        foreach ($rows as $row) {
            $pageKey = $this->buildPageKeyFromPcRow($row);
            if ($pageKey === '' || isset($seen[$pageKey])) {
                continue;
            }
            $seen[$pageKey] = true;
            $pages[] = [
                'page_key' => $pageKey,
                'user' => trim((string) ($row['user'] ?? '')),
                'computer_name' => trim((string) ($row['computer_name'] ?? '')),
            ];
        }

        return $pages;
    }

    private function buildPageKeyFromPcRow(array $row): string
    {
        $user = trim((string) ($row['user'] ?? ''));
        if ($user !== '') {
            return 'user:' . mb_strtolower($user);
        }

        $computerName = trim((string) ($row['computer_name'] ?? ''));
        if ($computerName !== '') {
            return 'computer:' . mb_strtolower($computerName);
        }

        $inventoryId = trim((string) ($row['id_inventaris'] ?? ''));
        if ($inventoryId !== '') {
            return 'id:' . mb_strtolower($inventoryId);
        }

        return '';
    }

    private function resolveUserPageFromPcRow(array $userPages, array $pcRow, int $fallbackPage): int
    {
        $pageKey = $this->buildPageKeyFromPcRow($pcRow);
        foreach ($userPages as $index => $pageMeta) {
            if ((string) ($pageMeta['page_key'] ?? '') === $pageKey) {
                return $index + 1;
            }
        }

        return max(1, $fallbackPage);
    }

    private function buildUserPagination(array $context): array
    {
        $total = max(1, (int) ($context['total_user_pages'] ?? 1));
        $current = min($total, max(1, (int) ($context['current_user_page'] ?? 1)));
        $divisionCode = (string) ($context['division_code'] ?? '');
        $displayDivision = (string) ($context['display_division'] ?? '');

        $makeHref = function (int $pageNo) use ($divisionCode, $displayDivision): string {
            return 'index.php?' . http_build_query([
                'page' => 'inventaris-detail',
                'division_code' => $divisionCode,
                'display_division' => $displayDivision,
                'user_page' => $pageNo,
                'user' => '',
                'email' => '',
                'focus_item' => '',
            ]);
        };

        $pages = [];
        for ($i = 1; $i <= $total; $i++) {
            $pages[] = [
                'number' => $i,
                'href' => $makeHref($i),
                'is_active' => $i === $current,
            ];
        }

        return [
            'current' => $current,
            'total' => $total,
            'pages' => $pages,
            'prev' => $current > 1 ? ['number' => $current - 1, 'href' => $makeHref($current - 1)] : null,
            'next' => $current < $total ? ['number' => $current + 1, 'href' => $makeHref($current + 1)] : null,
        ];
    }

    private function fetchItSupportNotifications(PDO $pdo): array
    {
        try {
            try {
                $pdo->exec("ALTER TABLE it_support_request ADD COLUMN notification_read_at DATETIME NULL AFTER updated_at");
            } catch (Throwable $e) {
                // ignore jika kolom sudah ada
            }

            $whereUnread = "notification_read_at IS NULL AND UPPER(TRIM(COALESCE(status, 'NOT YET'))) = 'NOT YET'";
            $count = 0;
            $countStmt = $pdo->query("SELECT COUNT(*) FROM it_support_request WHERE " . $whereUnread);
            if ($countStmt) {
                $count = (int) $countStmt->fetchColumn();
            }

            $sql = "SELECT id, ticket_no, nama_pelapor AS nama, divisi, aset_yang_perlu_diperbaiki AS barang, CONCAT(DATE_FORMAT(tanggal, '%d/%m/%Y'), ' ', TIME_FORMAT(jam, '%H:%i')) AS tanggal_dan_jam FROM it_support_request WHERE " . $whereUnread . " ORDER BY tanggal DESC, jam DESC, id DESC LIMIT 8";
            $stmt = $pdo->query($sql);
            $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return ["count" => $count, "items" => $items ?: []];
        } catch (Throwable $e) {
            return ["count" => 0, "items" => []];
        }
    }
    private function fetchComplaintRows(PDO $pdo, array $filters = []): array
    {
        try {
            try { $pdo->exec("ALTER TABLE it_support_request ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email_pelapor"); } catch (Throwable $e) {}
            try { $pdo->exec("ALTER TABLE it_support_request ADD COLUMN notification_read_at DATETIME NULL AFTER updated_at"); } catch (Throwable $e) {}
            try { $pdo->exec("ALTER TABLE it_support_request ADD COLUMN handling_email_sent_at DATETIME NULL AFTER handled_by_user_id"); } catch (Throwable $e) {}
            try { $pdo->exec("ALTER TABLE it_support_request ADD COLUMN handling_email_status VARCHAR(30) NULL AFTER handling_email_sent_at"); } catch (Throwable $e) {}
            try { $pdo->exec("ALTER TABLE it_support_request ADD COLUMN handling_email_message VARCHAR(255) NULL AFTER handling_email_status"); } catch (Throwable $e) {}
            try { $pdo->exec("UPDATE it_support_request SET status = 'NOT YET' WHERE status IS NULL OR TRIM(CAST(status AS CHAR)) = '' OR TRIM(CAST(status AS CHAR)) = '0'"); } catch (Throwable $e) {}
            $where = [];
            $params = [];

            $status = strtoupper(trim((string) ($filters['complaint_status'] ?? '')));
            if ($status !== '' && in_array($status, ['NOT YET', 'ON PROGRESS', 'DONE'], true)) {
                $where[] = 'UPPER(TRIM(r.status)) = :status';
                $params['status'] = $status;
            }

            $division = trim((string) ($filters['complaint_division'] ?? ''));
            if ($division !== '') {
                $divisionShort = trim((string) preg_replace('/\s*[&\(\/-].*$/', '', $division));
                $where[] = '(UPPER(TRIM(r.divisi)) = :division OR (:division_short <> "" AND UPPER(TRIM(r.divisi)) = :division_short))';
                $params['division'] = mb_strtoupper($division);
                $params['division_short'] = mb_strtoupper($divisionShort);
            }

            $dateFrom = trim((string) ($filters['complaint_date_from'] ?? ''));
            if ($this->isValidDateInput($dateFrom)) {
                $where[] = 'r.tanggal >= :date_from';
                $params['date_from'] = $dateFrom;
            }

            $dateTo = trim((string) ($filters['complaint_date_to'] ?? ''));
            if ($this->isValidDateInput($dateTo)) {
                $where[] = 'r.tanggal <= :date_to';
                $params['date_to'] = $dateTo;
            }

            $search = trim((string) ($filters['complaint_search'] ?? ''));
            if ($search !== '') {
                $where[] = '(r.ticket_no LIKE :search OR r.email_pelapor LIKE :search OR r.nama_pelapor LIKE :search OR r.divisi LIKE :search OR r.aset_yang_perlu_diperbaiki LIKE :search OR r.lokasi_perbaikan LIKE :search OR r.deskripsi_kerusakan LIKE :search OR COALESCE(r.catatan_penanganan, "") LIKE :search OR COALESCE(u.nama_lengkap, "") LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }

            $sql = '
                SELECT
                    r.id,
                    r.ticket_no,
                    r.tanggal AS tanggal_raw,
                    r.jam AS jam_raw,
                    CONCAT(DATE_FORMAT(r.tanggal, "%d %M %Y"), " ", TIME_FORMAT(r.jam, "%H:%i:%s")) AS tanggal_dan_jam,
                    r.email_pelapor AS email,
                    r.nama_pelapor AS nama,
                    r.divisi,
                    r.aset_yang_perlu_diperbaiki AS barang,
                    r.lokasi_perbaikan AS lokasi,
                    r.deskripsi_kerusakan AS deskripsi,
                    r.dokumentasi_kerusakan AS dokumentasi,
                    r.status,
                    r.catatan_penanganan,
                    r.handled_by_user_id,
                    r.handling_email_sent_at,
                    r.handling_email_status,
                    r.handling_email_message,
                    COALESCE(NULLIF(u.nama_lengkap, ""), NULLIF(u.username, ""), NULLIF(u.email, ""), "") AS handled_by_name
                FROM it_support_request r
                LEFT JOIN users u ON u.id = r.handled_by_user_id';

            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY r.tanggal DESC, r.jam DESC, r.id DESC LIMIT 200';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            if (!$rows) {
                return [];
            }

            $divisionLabelMap = $this->fetchMasterDivisionLabelMap($pdo);
            $result = [];
            foreach ($rows as $row) {
                $status = strtoupper(trim((string) ($row['status'] ?? 'NOT YET')));
                if ($status === '' || $status === '0') {
                    $status = 'NOT YET';
                }
                if ($status === '1') {
                    $status = 'ON PROGRESS';
                }
                if ($status === '2') {
                    $status = 'DONE';
                }
                $email = (string) ($row['email'] ?? '');
                $dateTime = (string) ($row['tanggal_dan_jam'] ?? '');
                $displayDivision = $this->normalizeDivisionLabelForDisplay((string) ($row['divisi'] ?? ''), $divisionLabelMap);
                $result[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'ticket_no' => (string) ($row['ticket_no'] ?? '-'),
                    'date_value' => (string) ($row['tanggal_raw'] ?? ''),
                    'time_value' => (string) ($row['jam_raw'] ?? ''),
                    'datetime' => $this->multilineDateTime($dateTime),
                    'datetime_plain' => $dateTime,
                    'email' => $this->multilineEmail($email),
                    'email_plain' => $email,
                    'name' => (string) ($row['nama'] ?? '-'),
                    'division' => $displayDivision !== '' ? $displayDivision : (string) ($row['divisi'] ?? '-'),
                    'division_raw' => (string) ($row['divisi'] ?? ''),
                    'item' => (string) ($row['barang'] ?? '-'),
                    'location' => (string) ($row['lokasi'] ?? '-'),
                    'description' => (string) ($row['deskripsi'] ?? '-'),
                    'doc_image' => $this->normalizeAssetPath((string) ($row['dokumentasi'] ?? '')),
                    'status' => $status,
                    'status_class' => $this->mapStatusClass($status),
                    'catatan_penanganan' => (string) ($row['catatan_penanganan'] ?? ''),
                    'handled_by_user_id' => (int) ($row['handled_by_user_id'] ?? 0),
                    'handled_by_name' => (string) ($row['handled_by_name'] ?? ''),
                    'handling_email_sent_at' => (string) ($row['handling_email_sent_at'] ?? ''),
                    'handling_email_status' => (string) ($row['handling_email_status'] ?? ''),
                    'handling_email_message' => (string) ($row['handling_email_message'] ?? ''),
                ];
            }

            return $result;
        } catch (Throwable $e) {
            return [];
        }
    }



    private function fetchItHandlerOptions(PDO $pdo): array
    {
        try {
            $sql = '
                SELECT DISTINCT
                    u.id,
                    COALESCE(NULLIF(u.nama_lengkap, ""), NULLIF(u.username, ""), NULLIF(u.email, "")) AS nama,
                    u.email,
                    u.username,
                    u.role,
                    u.unit_kerja_default
                FROM users u
                LEFT JOIN master_divisi d ON d.id = u.default_divisi_id
                WHERE u.is_active = 1
                  AND COALESCE(NULLIF(u.nama_lengkap, ""), NULLIF(u.username, ""), NULLIF(u.email, "")) IS NOT NULL
                  AND UPPER(TRIM(COALESCE(u.nama_lengkap, ""))) NOT IN ("", "-")
                  AND UPPER(TRIM(COALESCE(u.username, ""))) NOT IN ("USER", "MONITOR.CCTV")
                  AND (
                        (UPPER(TRIM(COALESCE(d.inventory_db_name, ""))) = "DB_SPMT_TEKNIK_DAN_IT" AND UPPER(TRIM(COALESCE(u.unit_kerja_default, ""))) = "IT")
                        OR u.role IN ("admin", "operator")
                  )
                ORDER BY
                    CASE WHEN UPPER(TRIM(COALESCE(u.unit_kerja_default, ""))) = "IT" THEN 0 ELSE 1 END,
                    nama ASC
            ';
            $stmt = $pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $out = [];
            foreach ($rows as $row) {
                $name = trim((string) ($row['nama'] ?? ''));
                if ($name === '' || $name === '-') {
                    continue;
                }
                $out[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'name' => $name,
                    'email' => (string) ($row['email'] ?? ''),
                    'unit' => (string) ($row['unit_kerja_default'] ?? ''),
                    'role' => (string) ($row['role'] ?? ''),
                ];
            }
            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }
    private function fetchComplaintHistoryMap(PDO $pdo, array $rows): array
    {
        if (!$rows) {
            return [];
        }

        try {
            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS it_support_request_history (
  id BIGINT NOT NULL AUTO_INCREMENT,
  request_id BIGINT NOT NULL,
  ticket_no VARCHAR(30) NOT NULL,
  old_status ENUM('NOT YET','ON PROGRESS','DONE') NOT NULL,
  new_status ENUM('NOT YET','ON PROGRESS','DONE') NOT NULL,
  old_catatan_penanganan TEXT NULL,
  new_catatan_penanganan TEXT NULL,
  changed_by_user_id BIGINT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_it_support_history_request (request_id),
  KEY idx_it_support_history_ticket (ticket_no),
  KEY idx_it_support_history_changed_by (changed_by_user_id),
  KEY idx_it_support_history_changed_at (changed_at),
  CONSTRAINT fk_it_support_history_request FOREIGN KEY (request_id) REFERENCES it_support_request (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_it_support_history_changed_by FOREIGN KEY (changed_by_user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        } catch (Throwable $e) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if (!$ids) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT h.request_id, h.old_status, h.new_status, h.old_catatan_penanganan, h.new_catatan_penanganan, h.changed_at, COALESCE(NULLIF(u.nama_lengkap, ''), NULLIF(u.username, ''), NULLIF(u.email, ''), 'User IT') AS changed_by_name FROM it_support_request_history h LEFT JOIN users u ON u.id = h.changed_by_user_id WHERE h.request_id IN ($placeholders) ORDER BY h.changed_at DESC, h.id DESC LIMIT 500";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($ids));
            $fetched = $stmt->fetchAll();
            if (!$fetched) {
                return [];
            }

            $map = [];
            foreach ($fetched as $entry) {
                $requestId = (int) ($entry['request_id'] ?? 0);
                if ($requestId <= 0) {
                    continue;
                }
                $map[$requestId] = $map[$requestId] ?? [];
                if (count($map[$requestId]) >= 10) {
                    continue;
                }
                $changedAt = trim((string) ($entry['changed_at'] ?? ''));
                $map[$requestId][] = [
                    'old_status' => (string) ($entry['old_status'] ?? '-'),
                    'new_status' => (string) ($entry['new_status'] ?? '-'),
                    'old_notes' => (string) ($entry['old_catatan_penanganan'] ?? ''),
                    'new_notes' => (string) ($entry['new_catatan_penanganan'] ?? ''),
                    'changed_at' => $changedAt,
                    'changed_at_label' => $this->formatHistoryDateTime($changedAt),
                    'changed_by' => (string) ($entry['changed_by_name'] ?? 'User IT'),
                ];
            }

            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function formatHistoryDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '-';
        }

        try {
            $dt = new DateTimeImmutable($value);
            return $this->formatIndonesianDate($dt) . ' ' . $dt->format('H:i:s');
        } catch (Throwable $e) {
            return $value;
        }
    }

    private function buildCctvBreakdown(PDO $pdo, array $fallback): array
    {
        try {
            $this->ensureCctvTable($pdo);
            $stmt = $pdo->query('SELECT id, lokasi, jumlah, color FROM dashboard_cctv ORDER BY id ASC');
            $rows = $stmt ? $stmt->fetchAll() : [];
            if (!$rows) {
                $this->seedDefaultCctv($pdo, $fallback);
                $stmt = $pdo->query('SELECT id, lokasi, jumlah, color FROM dashboard_cctv ORDER BY id ASC');
                $rows = $stmt ? $stmt->fetchAll() : [];
            }
            $result = [];
            foreach ($rows as $row) {
                $label = trim((string) ($row['lokasi'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $result[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'label' => $label,
                    'value' => (int) ($row['jumlah'] ?? 0),
                    'color' => preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($row['color'] ?? '')) ? (string) $row['color'] : '#5B8DEF',
                ];
            }
            return $result ?: $fallback;
        } catch (Throwable $e) {
            return $fallback;
        }
    }

    public function ensureCctvTable(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS dashboard_cctv (
            id INT NOT NULL AUTO_INCREMENT,
            lokasi VARCHAR(150) NOT NULL,
            jumlah INT NOT NULL DEFAULT 0,
            color CHAR(7) NOT NULL DEFAULT "#5B8DEF",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function seedDefaultCctv(PDO $pdo, array $fallback): void
    {
        $check = $pdo->query('SELECT COUNT(*) AS total FROM dashboard_cctv');
        $total = $check ? (int) ($check->fetch()['total'] ?? 0) : 0;
        if ($total > 0) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO dashboard_cctv (lokasi, jumlah, color) VALUES (:lokasi, :jumlah, :color)');
        foreach ($fallback as $item) {
            $stmt->execute([
                'lokasi' => (string) ($item['label'] ?? '-'),
                'jumlah' => max(0, (int) ($item['value'] ?? 0)),
                'color' => preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($item['color'] ?? '')) ? (string) $item['color'] : '#5B8DEF',
            ]);
        }
    }

    private function buildComplaintFilters(PDO $pdo, array $filters = []): array
    {
        $divisionOptions = $this->fetchMasterDivisionLabels($pdo);

        return [
            'status' => strtoupper(trim((string) ($filters['complaint_status'] ?? ''))),
            'division' => trim((string) ($filters['complaint_division'] ?? '')),
            'search' => trim((string) ($filters['complaint_search'] ?? '')),
            'date_from' => trim((string) ($filters['complaint_date_from'] ?? '')),
            'date_to' => trim((string) ($filters['complaint_date_to'] ?? '')),
            'division_options' => $divisionOptions,
            'total' => count($this->fetchComplaintRows($pdo, $filters)),
        ];
    }

    private function fetchMasterDivisionLabels(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query('SELECT division_label FROM master_divisi WHERE is_active = 1 ORDER BY sheet_sumber ASC, division_label ASC');
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $labels = [];
            foreach ($rows as $row) {
                $label = trim((string) ($row['division_label'] ?? ''));
                if ($label !== '' && !in_array($label, $labels, true)) {
                    $labels[] = $label;
                }
            }
            return $labels;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function fetchMasterDivisionLabelMap(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query('SELECT division_code, division_group_name, division_label FROM master_divisi WHERE is_active = 1 ORDER BY id ASC');
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return $this->buildDivisionAliasMap($rows, false);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function fetchMasterDivisionChartMeta(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query('SELECT division_code, division_group_name, division_label, sheet_sumber FROM master_divisi WHERE is_active = 1 ORDER BY sheet_sumber ASC, division_label ASC');
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $labels = [];
            foreach ($rows as $row) {
                $label = trim((string) ($row['division_label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $displayLabel = $this->formatDivisionWithSource($label, (string) ($row['sheet_sumber'] ?? ''));
                if ($displayLabel !== '' && !in_array($displayLabel, $labels, true)) {
                    $labels[] = $displayLabel;
                }
            }

            return [
                'labels' => $labels,
                'map' => $this->buildDivisionAliasMap($rows, true),
            ];
        } catch (Throwable $e) {
            return [
                'labels' => [],
                'map' => [],
            ];
        }
    }

    private function buildDivisionAliasMap(array $rows, bool $includeSource): array
    {
        $map = [];
        foreach ($rows as $row) {
            $label = trim((string) ($row['division_label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $displayLabel = $includeSource ? $this->formatDivisionWithSource($label, (string) ($row['sheet_sumber'] ?? '')) : $label;
            $displayAlias = $this->normalizeDivisionKey((string) $displayLabel);
            if ($displayAlias !== '' && !isset($map[$displayAlias])) {
                $map[$displayAlias] = $displayLabel;
            }
            foreach (['division_label', 'division_code', 'division_group_name'] as $key) {
                $alias = $this->normalizeDivisionKey((string) ($row[$key] ?? ''));
                if ($alias !== '' && !isset($map[$alias])) {
                    $map[$alias] = $displayLabel;
                }
            }
            foreach (preg_split('/[&\/-]+/', $label) ?: [] as $part) {
                $alias = $this->normalizeDivisionKey((string) $part);
                if ($alias !== '' && !isset($map[$alias])) {
                    $map[$alias] = $displayLabel;
                }
            }
        }
        return $map;
    }

    private function formatDivisionWithSource(string $label, string $source): string
    {
        $label = strtoupper(trim($label));
        $source = strtoupper(trim($source));
        if ($label === '') {
            return '';
        }
        if ($source === '') {
            return $label;
        }
        if (preg_match('/\b' . preg_quote($source, '/') . '\b/i', $label)) {
            return $label;
        }
        return $label . ' - ' . $source;
    }

    private function normalizeDivisionLabelForDisplay(string $division, array $divisionLabelMap): string
    {
        $division = trim($division);
        if ($division === '') {
            return '';
        }

        $key = $this->normalizeDivisionKey($division);
        if ($key !== '' && isset($divisionLabelMap[$key])) {
            return $divisionLabelMap[$key];
        }

        foreach ($divisionLabelMap as $alias => $label) {
            if ($alias !== '' && (str_contains($key, $alias) || str_contains($alias, $key))) {
                return $label;
            }
        }

        return strtoupper($division);
    }

    private function normalizeDivisionKey(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = str_replace(['&', '-', '/', '_'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?: '';
        return trim($value);
    }

    private function isValidDateInput(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function buildComplaintChart(PDO $pdo, array $fallback): array
    {
        try {
            $chartMeta = $this->fetchMasterDivisionChartMeta($pdo);
            $divisionLabelMap = $chartMeta['map'] ?? [];
            $labels = $chartMeta['labels'] ?? [];

            // Dashboard selalu mengikuti bulan kalender berjalan pada tahun ini.
            // Saat bulan berganti, query otomatis pindah ke bulan baru tanpa perlu edit kode.
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
            $currentYear = (int) $now->format('Y');
            $currentMonth = (int) $now->format('n');
            $stmt = $pdo->prepare('SELECT divisi, COUNT(*) AS total FROM it_support_request WHERE YEAR(tanggal) = :year AND MONTH(tanggal) = :month GROUP BY divisi ORDER BY divisi ASC');
            $stmt->execute([
                'year' => $currentYear,
                'month' => $currentMonth,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $totalsByDivision = [];
            foreach ($rows as $row) {
                $division = $this->normalizeDivisionLabelForDisplay((string) ($row['divisi'] ?? ''), $divisionLabelMap);
                $total = (int) ($row['total'] ?? 0);
                if ($division === '') {
                    continue;
                }
                if (!in_array($division, $labels, true)) {
                    $labels[] = $division;
                }
                $totalsByDivision[$division] = (int) (($totalsByDivision[$division] ?? 0) + $total);
            }

            if (!$labels) {
                return $fallback;
            }

            $data = [];
            $items = [];
            foreach ($labels as $division) {
                $total = (int) ($totalsByDivision[$division] ?? 0);
                $data[] = $total;
                $items[] = [
                    'label' => $division,
                    'short_label' => $this->shortenDivisionLabel($division),
                    'total' => $total,
                ];
            }

            return [
                'labels' => $labels,
                'series' => [[
                    'label' => strtoupper($this->monthShortName($currentMonth)) . ' ' . $currentYear,
                    'color' => '#41B8D5',
                    'data' => $data,
                ]],
                'items' => $items,
                'total' => array_sum($data),
                'month' => strtoupper($this->monthName($currentMonth)),
                'month_short' => strtoupper($this->monthShortName($currentMonth)),
                'year' => $currentYear,
                'period_label' => strtoupper($this->monthName($currentMonth)) . ' ' . $currentYear,
            ];
        } catch (Throwable $e) {
            return $fallback;
        }
    }
    private function shortenDivisionLabel(string $label): string
    {
        $label = strtoupper(trim($label));
        $label = preg_replace('/\s+/', ' ', $label) ?: $label;
        $replacements = [
            'DIVISI ' => '',
            'DAN ' => '& ',
            'PROPERTI SDM UMUM' => 'PROP. SDM UMUM',
            'RUANG RAPAT DAN BRANCH MANAGER' => 'RUANG RAPAT/BM',
            'PENDUKUNG OPERASI' => 'PENDUKUNG OPS',
            'RENDAL OPERASI' => 'RENDAL OPS',
            'OPERASIONAL' => 'OPS',
        ];
        foreach ($replacements as $search => $replace) {
            $label = str_replace($search, $replace, $label);
        }
        return trim($label);
    }

    private function buildInventoryFlow(PDO $pdo, array $filters = []): array
    {
        $state = $this->normalizeLogFilterState($filters);
        try {
            // Dashboard tanpa filter selalu mengikuti bulan kalender berjalan pada tahun ini.
            // Filter eksplisit di halaman Log Barang tetap dihormati.
            $hasExplicitPeriod = array_key_exists('log_year', $filters) || array_key_exists('log_month', $filters) || array_key_exists('log_date', $filters);
            if (!$hasExplicitPeriod) {
                $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
                $state['year'] = (int) $now->format('Y');
                $state['month'] = (int) $now->format('n');
                $state['date'] = '';
            }

            $rows = $this->fetchInventoryFlowRows($pdo, $state);
            $masuk = 0;
            $keluar = 0;
            foreach ($rows as $row) {
                $status = (string) ($row['status_name'] ?? '');
                $total = (int) ($row['total'] ?? 0);
                if ($status === 'MASUK') {
                    $masuk = $total;
                } elseif ($status === 'KELUAR') {
                    $keluar = $total;
                }
            }

            return [
                'labels' => ['BARANG MASUK', 'BARANG KELUAR'],
                'data' => [$masuk, $keluar],
                'month' => $state['month'] > 0 ? strtoupper($this->monthName($state['month'])) : 'SEMUA BULAN',
                'year' => $state['year'],
            ];
        } catch (Throwable $e) {
            return $this->getBaseData()['inventory_flow'];
        }
    }

    private function fetchInventoryFlowRows(PDO $pdo, array $state): array
    {
        $where = ['YEAR(tanggal) = :year'];
        $params = ['year' => $state['year']];
        if ((int) ($state['month'] ?? 0) > 0) {
            $where[] = 'MONTH(tanggal) = :month';
            $params['month'] = (int) $state['month'];
        }
        if ((string) ($state['date'] ?? '') !== '') {
            $where[] = 'tanggal = :date';
            $params['date'] = (string) $state['date'];
        }

        $sql = 'SELECT UPPER(status) AS status_name, COALESCE(SUM(qty), 0) AS total FROM log_barang WHERE ' . implode(' AND ', $where) . ' GROUP BY UPPER(status)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    private function fetchLogRows(PDO $pdo, array $filters = []): array
    {
        $state = $this->normalizeLogFilterState($filters);
        try {
            $where = ['YEAR(tanggal) = :year'];
            $params = ['year' => $state['year']];

            if ($state['month'] > 0) {
                $where[] = 'MONTH(tanggal) = :month';
                $params['month'] = $state['month'];
            }
            if ($state['date'] !== '') {
                $where[] = 'tanggal = :date';
                $params['date'] = $state['date'];
            }
            if ($state['status'] !== '') {
                $where[] = 'UPPER(status) = :status';
                $params['status'] = $state['status'];
            }
            if ($state['search'] !== '') {
                $where[] = '(log_no LIKE :search OR nama_barang LIKE :search OR COALESCE(no_po, "") LIKE :search OR COALESCE(divisi, "") LIKE :search OR COALESCE(keterangan, "") LIKE :search)';
                $params['search'] = '%' . $state['search'] . '%';
            }

            $orderSql = $state['sort'] === 'oldest' ? 'ASC' : 'DESC';
            $sql = 'SELECT id, log_no, tanggal, created_at, nama_barang, status, qty, no_po, surat_pemesanan_pdf, divisi, keterangan FROM log_barang WHERE '
                . implode(' AND ', $where)
                . ' ORDER BY tanggal ' . $orderSql . ', created_at ' . $orderSql . ', id ' . $orderSql . ' LIMIT 500';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            if (!$rows) {
                return [];
            }

            $result = [];
            $number = 1;
            foreach ($rows as $row) {
                $status = strtoupper((string) ($row['status'] ?? 'MASUK'));
                $pdf = trim((string) ($row['surat_pemesanan_pdf'] ?? ''));
                $result[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'no' => $number++,
                    'date' => $this->formatDateShort((string) ($row['tanggal'] ?? '')),
                    'created_time' => (function($dt) {
                        if (!$dt) return '-';
                        $ts = strtotime($dt);
                        return $ts ? date('d/m/Y H:i', $ts) : '-';
                    })((string) ($row['created_at'] ?? '')),
                    'raw_date' => (string) ($row['tanggal'] ?? ''),
                    'datetime' => trim((string) (($row['tanggal'] ?? '') . ' ' . ($row['created_at'] ?? ''))),
                    'item' => (string) ($row['nama_barang'] ?? '-'),
                    'status' => $status,
                    'status_class' => $status === 'KELUAR' ? 'out' : 'in',
                    'qty' => (int) ($row['qty'] ?? 1),
                    'no_po' => (string) ($row['no_po'] ?? '-'),
                    'pdf' => $pdf,
                    'pdf_name' => $pdf !== '' ? basename($pdf) : '',
                    'division' => (string) ($row['divisi'] ?? '-'),
                    'created_time' => (function($dt) {
                        if (!$dt) return '-';
                        $ts = strtotime((string) $dt);
                        return $ts ? date('d/m/Y H:i', $ts) : '-';
                    })($row['created_at'] ?? ''),
                    'log_no' => (string) ($row['log_no'] ?? '-'),
                    'keterangan' => (string) ($row['keterangan'] ?? ''),
                ];
            }

            return $result;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function buildLogFilters(PDO $pdo, array $filters = []): array
    {
        $state = $this->normalizeLogFilterState($filters);
        $years = [];
        try {
            $stmt = $pdo->query('SELECT DISTINCT YEAR(tanggal) AS tahun FROM log_barang ORDER BY tahun DESC');
            foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
                $year = (int) ($row['tahun'] ?? 0);
                if ($year > 0) {
                    $years[] = $year;
                }
            }
        } catch (Throwable $e) {
        }
        if (!$years) {
            $years[] = (int) date('Y');
        }
        if (!in_array($state['year'], $years, true)) {
            $state['year'] = $years[0];
        }

        $months = [0 => 'Semua Bulan'];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = $this->monthName($i);
        }

        return [
            'selected' => $state,
            'years' => $years,
            'months' => $months,
            'date_value' => $state['date'],
            'statuses' => [
                '' => 'Semua Status',
                'MASUK' => 'Barang Masuk',
                'KELUAR' => 'Barang Keluar',
            ],
            'sorts' => [
                'newest' => 'Tanggal terbaru',
                'oldest' => 'Tanggal terlama',
            ],
        ];
    }

    private function normalizeLogFilterState(array $filters): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
        $defaultYear = (int) $now->format('Y');
        $defaultMonth = (int) $now->format('n');

        $year = (int) ($filters['log_year'] ?? $defaultYear);
        if ($year < 2000 || $year > 2100) {
            $year = $defaultYear;
        }
        $month = (int) ($filters['log_month'] ?? $defaultMonth);
        if ($month < 0 || $month > 12) {
            $month = $defaultMonth;
        }
        $date = trim((string) ($filters['log_date'] ?? ''));
        if ($date !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
            if (!$dt || $dt->format('Y-m-d') !== $date) {
                $date = '';
            } else {
                $year = (int) $dt->format('Y');
                $month = (int) $dt->format('n');
            }
        }
        $status = strtoupper(trim((string) ($filters['log_status'] ?? '')));
        if (!in_array($status, ['', 'MASUK', 'KELUAR'], true)) {
            $status = '';
        }
        $sort = trim((string) ($filters['log_sort'] ?? 'newest'));
        if (!in_array($sort, ['newest', 'oldest'], true)) {
            $sort = 'newest';
        }
        return [
            'year' => $year,
            'month' => $month,
            'date' => $date,
            'search' => trim((string) ($filters['log_search'] ?? '')),
            'status' => $status,
            'sort' => $sort,
        ];
    }

    private function defaultCategoryCards(): array
    {
        return [
            ['icon' => 'fa-solid fa-gears', 'label' => "DIVISI\nPENDUKUNG\nOPERASI", 'sub_label' => 'SPMT', 'route_url' => 'index.php?page=inventaris-detail&division_code=SPMT_PENDUKUNG_OPERASI&display_division=DIVISI%20PENDUKUNG%20OPERASI'],
            ['icon' => 'fa-solid fa-desktop', 'label' => "TEKNIK\n%26\nIT", 'sub_label' => 'SPMT', 'route_url' => 'index.php?page=inventaris-detail&division_code=SPMT_TEKNIK_IT&display_division=TEKNIK%20%26%20IT'],
            ['icon' => 'fa-solid fa-gears', 'label' => "RENDAL\nOPS", 'sub_label' => 'SPMT', 'route_url' => 'index.php?page=inventaris-detail&division_code=SPMT_RENDAL_OPS&display_division=RENDAL%20OPS'],
            ['icon' => 'fa-solid fa-lightbulb', 'label' => "INTEGRATED\nPNC", 'sub_label' => 'SPMT', 'route_url' => 'index.php?page=inventaris-detail&division_code=SPMT_INTEGRATED_PNC&display_division=INTEGRATED%20PNC'],
            ['icon' => 'fa-solid fa-gears', 'label' => "DIVISI OPERASIONAL", 'sub_label' => 'SPMT', 'route_url' => 'index.php?page=inventaris-detail&division_code=SPMT_OPERASIONAL&display_division=OPERASIONAL'],
            ['icon' => 'fa-solid fa-briefcase', 'label' => "RUANG\nRAPAT\n%26\nBRANCH\nMANAGER", 'sub_label' => 'SPMT', 'route_url' => 'index.php?page=inventaris-detail&division_code=SPMT_RUANG_RAPAT_BRANCH_MANAGER&display_division=RUANG%20RAPAT%20%26%20BRANCH%20MANAGER'],
            ['icon' => 'fa-solid fa-layer-group', 'label' => "SUBREG", 'sub_label' => 'OFF DULU', 'route_url' => null, 'is_disabled' => true],
        ];
    }

    private function mapDivisionIcon(string $divisionCode, string $displayLabel): string
    {
        $text = strtoupper($divisionCode . ' ' . $displayLabel);

        if ($this->contains($text, 'TEKNIK') || $this->contains($text, 'IT')) {
            return 'fa-solid fa-desktop';
        }
        if ($this->contains($text, 'PNC')) {
            return 'fa-solid fa-lightbulb';
        }
        if ($this->contains($text, 'OPERASI')) {
            return 'fa-solid fa-gears';
        }
        if ($this->contains($text, 'KEUANGAN') || $this->contains($text, 'FINANCE')) {
            return 'fa-solid fa-wallet';
        }
        if ($this->contains($text, 'PROPERTI') || $this->contains($text, 'SDM') || $this->contains($text, 'UMUM')) {
            return 'fa-solid fa-building-user';
        }
        if ($this->contains($text, 'RUANG RAPAT') || $this->contains($text, 'BRANCH MANAGER')) {
            return 'fa-solid fa-briefcase';
        }

        return 'fa-solid fa-database';
    }

    private function normalizeDivisionLabel(string $divisionLabel, string $unitKerja): string
    {
        $label = strtoupper(trim($divisionLabel));
        $unit = strtoupper(trim($unitKerja));

        if ($unit === 'IT') {
            return 'DIVISI TEKNOLOGI INFORMASI';
        }
        if ($unit === 'TEKNIK') {
            return 'DIVISI TEKNIK';
        }
        if ($unit !== '') {
            return $unit;
        }

        if ($label === '') {
            return self::DEFAULT_DISPLAY_DIVISION;
        }
        if (
            $this->startsWith($label, 'DIVISI ') ||
            $this->startsWith($label, 'SUBREG') ||
            $this->startsWith($label, 'GATE ') ||
            $label === 'PELDAM'
        ) {
            return $label;
        }

        return 'DIVISI ' . $label;
    }

    private function mapImageByDevice(string $device): string
    {
        $device = strtoupper($device);

        if ($this->contains($device, 'MONITOR')) {
            return 'images/inv-monitor.png';
        }
        if ($this->contains($device, 'KEYBOARD')) {
            return 'images/inv-keyboard.png';
        }
        if ($this->contains($device, 'MOUSE')) {
            return 'images/inv-mouse.png';
        }
        if ($this->contains($device, 'PRINTER')) {
            return 'images/inv-printer.png';
        }
        if ($this->contains($device, 'LAPTOP')) {
            return 'images/inv-laptop.png';
        }
        if ($this->contains($device, 'CCTV') || $this->contains($device, 'WEBCAM')) {
            return 'images/inv-webcam.jpg';
        }
        if ($this->contains($device, 'SPEAKER')) {
            return 'images/inv-speaker.jpg';
        }

        return 'images/inv-default.jpg';
    }

    private function normalizeImagePath($path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('/\.(png|jpg|jpeg|webp)$/i', $path)) {
            return str_replace('public/assets/', '', str_replace('public/assets', '', str_replace('\\', '/', $path)));
        }
        return '';
    }

    private function resolveInventoryImage(array $row): string
    {
        $image = $this->normalizeImagePath($row['gambar'] ?? '');
        if ($image !== '') {
            return $image;
        }
        return $this->mapImageByDevice((string) ($row['jenis_perangkat'] ?? ''));
    }

    private function extractBrandFromText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '-';
        }
        $parts = preg_split('/\s*-\s*/', $text, 2);
        if (count($parts) === 2) {
            return trim($parts[1]) ?: '-';
        }
        return '-';
    }

    public function buildOtherItemKey(array $row): string
    {
        return md5(strtolower(trim((string) ($row['id_inventaris'] ?? ''))) . '|' . strtolower(trim((string) ($row['jenis_perangkat'] ?? ''))) . '|' . strtolower(trim((string) ($row['unit_kerja'] ?? ''))) . '|' . strtolower(trim((string) ($row['user'] ?? ''))) . '|' . strtolower(trim((string) ($row['merk_perangkat'] ?? ''))));
    }

    private function mapStatusClass(string $status): string
    {
        $status = strtoupper(trim($status));

        if (in_array($status, ['DONE', 'AKTIF', 'MASUK', 'LICENSED'], true)) {
            return 'good';
        }

        if (in_array($status, ['NOT YET', 'UNLICENSED', 'KELUAR', 'RUSAK'], true)) {
            return 'bad';
        }

        if ($status === 'ON PROGRESS') {
            return 'warn';
        }

        return 'good';
    }

    private function ensureInventoryMetaTables(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS inventory_update_log (
            id BIGINT NOT NULL AUTO_INCREMENT,
            division_code VARCHAR(100) NOT NULL,
            page_key VARCHAR(255) NOT NULL,
            inventory_db_name VARCHAR(255) NOT NULL,
            action_type VARCHAR(30) NOT NULL,
            item_scope VARCHAR(30) NOT NULL,
            item_key VARCHAR(255) NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_inventory_update_lookup (division_code, page_key, updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function logInventoryUpdate(PDO $pdo, string $divisionCode, string $pageKey, string $inventoryDb, string $actionType, string $itemScope, ?string $itemKey = null): void
    {
        $this->ensureInventoryMetaTables($pdo);
        $stmt = $pdo->prepare('INSERT INTO inventory_update_log (division_code, page_key, inventory_db_name, action_type, item_scope, item_key) VALUES (:division_code, :page_key, :inventory_db_name, :action_type, :item_scope, :item_key)');
        $stmt->execute([
            'division_code' => $divisionCode,
            'page_key' => $pageKey,
            'inventory_db_name' => $inventoryDb,
            'action_type' => $actionType,
            'item_scope' => $itemScope,
            'item_key' => $itemKey,
        ]);
    }


    public function getInventoryFormOptions(PDO $pdo): array
    {
        $divisions = [];
        try {
            $stmt = $pdo->query('SELECT id, division_code, division_label, inventory_db_name, sheet_sumber FROM master_divisi WHERE is_active = 1 ORDER BY id ASC');
            $divisions = $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable $e) {
            $divisions = [];
        }

        $users = [];
        try {
            $stmt = $pdo->query('SELECT u.id, u.nama_lengkap, u.email, u.default_divisi_id, COALESCE(NULLIF(u.unit_kerja_default, ""), md.division_label) AS unit_kerja_default FROM users u LEFT JOIN master_divisi md ON md.id = u.default_divisi_id WHERE u.is_active = 1 ORDER BY u.nama_lengkap ASC');
            $users = $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable $e) {
            $users = [];
        }

        return [
            'divisions' => $divisions,
            'users' => $users,
        ];
    }

    public function getDivisionByCode(PDO $pdo, string $divisionCode): ?array
    {
        return $this->fetchDivisionByCode($pdo, $divisionCode);
    }

    public function getDivisionContext(PDO $pdo, array $filters): array
    {
        return $this->resolveContext($pdo, $filters);
    }

    public function exportRowsForContext(PDO $pdo, array $context): array
    {
        return $this->buildInventoryRows($pdo, $context);
    }

    public function exportLogRows(PDO $pdo, array $filters = []): array
    {
        return $this->fetchLogRows($pdo, $filters);
    }

    public function exportComplaintRows(PDO $pdo, array $filters = []): array
    {
        return $this->fetchComplaintRows($pdo, $filters);
    }

    private function displayValue($value): string
    {
        $text = trim((string) $value);
        return $text === '' ? '-' : $text;
    }

    private function isSafeIdentifier(string $value): bool
    {
        return $value !== '' && (bool) preg_match('/^[A-Za-z0-9_]+$/', $value);
    }

    private function normalizeAssetPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        // IT Support files are stored in public/uploads/it-support.
        // Keep that prefix so asset() points to the uploaded file location.
        if (strpos($path, 'public/uploads/') === 0) {
            return $path;
        }

        if (strpos($path, 'public/assets/') === 0) {
            return substr($path, strlen('public/assets/'));
        }

        if (strpos($path, 'public/') === 0) {
            return $path;
        }

        return $path;
    }
    private function multilineDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '-';
        }

        $parts = preg_split('/\s+/', $value);
        if (!$parts) {
            return $value;
        }

        if (count($parts) >= 4) {
            return $parts[0] . ' ' . $parts[1] . ' ' . $parts[2] . "\n" . $parts[3];
        }

        return $value;
    }

    private function multilineEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '') {
            return '-';
        }

        return str_replace('@', "@\n", str_replace('.', ".\n", $email));
    }

    private function formatTimestamp(string $value): string
    {
        try {
            $date = new DateTimeImmutable($value);
            return $this->formatIndonesianDateTime($date);
        } catch (Throwable $e) {
            return $value;
        }
    }

    private function formatDateShort(string $value): string
    {
        try {
            $date = new DateTimeImmutable($value);
            return $date->format('d/m/Y');
        } catch (Throwable $e) {
            return $value;
        }
    }

    private function formatIndonesianDate(DateTimeImmutable $date): string
    {
        return $date->format('d') . ' ' . $this->monthName((int) $date->format('n')) . ' ' . $date->format('Y');
    }

    private function formatIndonesianTime(DateTimeImmutable $date): string
    {
        return $date->format('H:i:s');
    }

    private function formatIndonesianDateTime(DateTimeImmutable $date): string
    {
        return $date->format('d') . ' ' . $this->monthName((int) $date->format('n')) . ' ' . $date->format('Y') . ' ' . $date->format('H:i:s');
    }

    private function monthName(int $month): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[$month] ?? '-';
    }

    private function monthShortName(int $month): string
    {
        $months = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];

        return $months[$month] ?? '-';
    }

    private function contains(string $haystack, string $needle): bool
    {
        return strpos(strtoupper($haystack), strtoupper($needle)) !== false;
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }
}
