<?php

namespace Database\Seeders\ProjectManagement;

use App\Enums\RecordStatus;
use App\Models\ProjectManagement\Certification;
use App\Models\ProjectManagement\CertificationCategory;
use Illuminate\Database\Seeder;

/**
 * The standard certifications every installation starts with (owner,
 * 2026-11-02; ARCHITECTURE.md §9.14): the common ISO management systems, the
 * garment and textile compliance schemes, and the product standards met in
 * electrical, steel, pipe, machinery, radar and sheet tenders, plus the BSTI
 * mark. Shaped as {@see UnitSeeder}; runs after
 * {@see CertificationCategorySeeder} and finds each row's category by code.
 *
 * **Idempotent and additive.** A certification whose code is taken, or whose
 * name is taken in the same version — by any row, a deleted one included,
 * compared ignoring case — is skipped, as `CertificationRequest` would refuse
 * it. A row whose category code no longer exists (renamed or permanently
 * deleted) is skipped too, rather than filed somewhere else.
 *
 * Seeder rows are console writes, so they are not audited (§9.3).
 */
class CertificationSeeder extends Seeder
{
    /**
     * `[code, name, version, issuing body, category code]`.
     *
     * @var list<array{0: string, 1: string, 2: string|null, 3: string, 4: string}>
     */
    public const array CERTIFICATIONS = [
        ['ISO9001-2015', 'ISO 9001 Quality Management Systems', '2015', 'ISO', 'QMS'],
        ['ISO14001-2015', 'ISO 14001 Environmental Management Systems', '2015', 'ISO', 'EMS'],
        ['ISO45001-2018', 'ISO 45001 Occupational Health and Safety Management Systems', '2018', 'ISO', 'OHS'],
        ['ISO27001-2022', 'ISO/IEC 27001 Information Security Management Systems', '2022', 'ISO/IEC', 'ISMS'],
        ['ISO50001-2018', 'ISO 50001 Energy Management Systems', '2018', 'ISO', 'ENMS'],
        ['ISO22000-2018', 'ISO 22000 Food Safety Management Systems', '2018', 'ISO', 'FSMS'],
        ['HACCP', 'HACCP Hazard Analysis and Critical Control Points', null, 'Codex Alimentarius', 'FSMS'],
        ['ISO17025-2017', 'ISO/IEC 17025 Testing and Calibration Laboratories', '2017', 'ISO/IEC', 'LAB'],
        ['SA8000-2014', 'SA8000 Social Accountability', '2014', 'Social Accountability International', 'SOC'],
        ['BSCI', 'amfori BSCI', null, 'amfori', 'SOC'],
        ['WRAP', 'WRAP Worldwide Responsible Accredited Production', null, 'WRAP', 'SOC'],
        ['SMETA', 'SMETA Sedex Members Ethical Trade Audit', null, 'Sedex', 'SOC'],
        ['OEKO-TEX-100', 'OEKO-TEX Standard 100', null, 'OEKO-TEX Association', 'TXT'],
        ['GOTS', 'GOTS Global Organic Textile Standard', '7.0', 'Global Standard gGmbH', 'TXT'],
        ['GRS', 'GRS Global Recycled Standard', '4.0', 'Textile Exchange', 'TXT'],
        ['OCS', 'OCS Organic Content Standard', '3.0', 'Textile Exchange', 'TXT'],
        ['BLUESIGN', 'bluesign', null, 'bluesign technologies', 'TXT'],
        ['HIGG-FEM', 'Higg Facility Environmental Module', null, 'Cascale', 'TXT'],
        ['CE', 'CE Marking', null, 'European Union', 'ELEC'],
        ['UL', 'UL Listing', null, 'UL Solutions', 'ELEC'],
        ['ROHS', 'RoHS Restriction of Hazardous Substances', '2011/65/EU', 'European Union', 'ELEC'],
        ['IEC60947', 'IEC 60947 Low-voltage Switchgear and Controlgear', null, 'IEC', 'ELEC'],
        ['IEC61439', 'IEC 61439 Low-voltage Switchgear and Controlgear Assemblies', null, 'IEC', 'ELEC'],
        ['ISO17065-2012', 'ISO/IEC 17065 Product Certification Bodies', '2012', 'ISO/IEC', 'ELEC'],
        ['ISO1461-2022', 'ISO 1461 Hot-dip Galvanized Coatings on Iron and Steel', '2022', 'ISO', 'STEEL'],
        ['ASTM-A615', 'ASTM A615 Deformed Steel Bars for Concrete Reinforcement', null, 'ASTM International', 'STEEL'],
        ['BS4449', 'BS 4449 Steel for the Reinforcement of Concrete', '2005+A3:2016', 'BSI', 'STEEL'],
        ['ISO3834-2021', 'ISO 3834 Quality Requirements for Fusion Welding', '2021', 'ISO', 'STEEL'],
        ['AWS-D1-1', 'AWS D1.1 Structural Welding Code — Steel', '2020', 'American Welding Society', 'STEEL'],
        ['ASTM-A653', 'ASTM A653 Zinc-coated (Galvanized) Steel Sheet', null, 'ASTM International', 'STEEL'],
        ['JIS-G3302', 'JIS G3302 Hot-dip Zinc-coated Steel Sheets', null, 'Japanese Standards Association', 'STEEL'],
        ['EN10346-2015', 'EN 10346 Continuously Hot-dip Coated Steel Flat Products', '2015', 'CEN', 'STEEL'],
        ['ISO4427-2019', 'ISO 4427 Polyethylene (PE) Pipes for Water Supply', '2019', 'ISO', 'PIPE'],
        ['ISO1452-2009', 'ISO 1452 Unplasticized PVC (PVC-U) Pipes for Water Supply', '2009', 'ISO', 'PIPE'],
        ['ASTM-D3035', 'ASTM D3035 Polyethylene (PE) Plastic Pipe', null, 'ASTM International', 'PIPE'],
        ['ISO15874-2013', 'ISO 15874 Polypropylene (PP) Hot and Cold Water Piping Systems', '2013', 'ISO', 'PIPE'],
        ['ASME-U', 'ASME U Stamp — Pressure Vessels', null, 'ASME', 'MACH'],
        ['PED', 'Pressure Equipment Directive', '2014/68/EU', 'European Union', 'MACH'],
        ['ISO12100-2010', 'ISO 12100 Safety of Machinery — Risk Assessment', '2010', 'ISO', 'MACH'],
        ['API-Q1', 'API Spec Q1 Quality Management for the Petroleum Industry', '10th Ed.', 'American Petroleum Institute', 'MACH'],
        ['ITU-R-M1371', 'ITU-R M.1371 Automatic Identification System', null, 'ITU', 'RADAR'],
        ['IEC62388-2013', 'IEC 62388 Shipborne Radar', '2013', 'IEC', 'RADAR'],
        ['IEC60945-2002', 'IEC 60945 Maritime Navigation Equipment — Environmental Requirements', '2002', 'IEC', 'RADAR'],
        ['FCC', 'FCC Equipment Authorization', null, 'Federal Communications Commission', 'RADAR'],
        ['AS9100-D', 'AS9100 Aerospace Quality Management Systems', 'D', 'IAQG', 'RADAR'],
        ['BSTI-CM', 'BSTI Certification Mark', null, 'BSTI', 'NAT'],
        ['BDS-ISO9001', 'BDS ISO 9001 Quality Management Systems', '2015', 'BSTI', 'NAT'],
        ['BDS-ISO14001', 'BDS ISO 14001 Environmental Management Systems', '2015', 'BSTI', 'NAT'],
    ];

    /**
     * Add every standard certification whose code, and name within its
     * version, are still free.
     */
    public function run(): void
    {
        $taken = Certification::withTrashed()->get(['code', 'name', 'version']);

        $codes = $taken->mapWithKeys(fn (Certification $certification): array => [mb_strtoupper($certification->code) => true])->all();
        $names = $taken->mapWithKeys(fn (Certification $certification): array => [self::nameKey($certification->name, $certification->version) => true])->all();

        $categories = CertificationCategory::withTrashed()->pluck('id', 'code')
            ->mapWithKeys(fn (int $id, string $code): array => [mb_strtoupper($code) => $id])
            ->all();

        $now = now();
        $rows = [];

        foreach (self::CERTIFICATIONS as [$code, $name, $version, $issuingBody, $categoryCode]) {
            if (isset($codes[$code]) || isset($names[self::nameKey($name, $version)]) || ! isset($categories[$categoryCode])) {
                continue;
            }

            $rows[] = [
                'certification_category_id' => $categories[$categoryCode],
                'code' => $code,
                'name' => $name,
                'issuing_body' => $issuingBody,
                'version' => $version,
                'status' => RecordStatus::Active->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Certification::query()->insert($rows);
    }

    /**
     * A name and its version as one case-blind key; a blank version is an
     * edition of its own.
     */
    private static function nameKey(string $name, ?string $version): string
    {
        return mb_strtolower($name)."\0".mb_strtolower($version ?? '');
    }
}
