<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-01-14
 * Modified    : 2019-02-13
 * For LOVD    : 3.0-22
 *
 * Copyright   : 2004-2019 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *               Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.NL>
 *               M. Kroon <m.kroon@lumc.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

// Don't allow direct access.
if (!defined('ROOT_PATH')) {
    exit;
}

// How are the versions related?
$sCalcVersionFiles = lovd_calculateVersion($_SETT['system']['version']);
$sCalcVersionDB = lovd_calculateVersion($_STAT['version']);

if ($sCalcVersionFiles != $sCalcVersionDB) {
    // Version of files are not equal to version of database backend.

    // Increased execution time to help perform large upgrades.
    if ((int) ini_get('max_execution_time') < 60) {
        @set_time_limit(60);
    }

    // DB version greater than file version... then we have a problem.
    if ($sCalcVersionFiles < $sCalcVersionDB) {
        lovd_displayError('UpgradeError', 'Database version ' . $_STAT['version'] . ' found newer than file version ' . $_SETT['system']['version']);
    }

    define('PAGE_TITLE', 'Upgrading LOVD...');
    $_T->printHeader();
    $_T->printTitle();

    print('      Please wait while LOVD is upgrading the database backend from ' . $_STAT['version'] . ' to ' . $_SETT['system']['version'] . '.<BR><BR>' . "\n");

    // Array of changes.
    $aUpdates =
             array(
                    '3.0-pre-21' =>
                         array(
                                'UPGRADING TO 3.0-pre-21 IS NOT SUPPORTED. UNINSTALL LOVD 3.0 AND REINSTALL TO GET THE LATEST.',
                              ),
                    '3.0-alpha-01' =>
                         array(
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' DROP COLUMN edited_date',
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD COLUMN edited_date DATETIME AFTER edited_by',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' DROP COLUMN edited_date',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' ADD COLUMN edited_date DATETIME AFTER edited_by',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' DROP COLUMN edited_date',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' ADD COLUMN edited_date DATETIME AFTER edited_by',
                                'ALTER TABLE ' . TABLE_SCREENINGS . ' DROP COLUMN edited_date',
                                'ALTER TABLE ' . TABLE_SCREENINGS . ' ADD COLUMN edited_date DATETIME AFTER edited_by',
                                'ALTER TABLE ' . TABLE_GENES . ' MODIFY COLUMN id VARCHAR(20) NOT NULL',
                                'ALTER TABLE ' . TABLE_CURATES . ' MODIFY COLUMN geneid VARCHAR(20) NOT NULL',
                                'ALTER TABLE ' . TABLE_TRANSCRIPTS . ' MODIFY COLUMN geneid VARCHAR(20) NOT NULL',
                                'ALTER TABLE ' . TABLE_GEN2DIS . ' MODIFY COLUMN geneid VARCHAR(20) NOT NULL',
                                'ALTER TABLE ' . TABLE_SCR2GENE . ' MODIFY COLUMN geneid VARCHAR(20) NOT NULL',
                                'ALTER TABLE ' . TABLE_SHARED_COLS . ' MODIFY COLUMN geneid VARCHAR(20)',
                                'ALTER TABLE ' . TABLEPREFIX . '_hits MODIFY COLUMN geneid VARCHAR(20) NOT NULL',
                              ),
                    '3.0-alpha-02' =>
                         array(
                                'UPDATE ' . TABLE_COLS . ' SET select_options = "Unknown\r\nBESS = Base Excision Sequence Scanning\r\nCMC = Chemical Mismatch Cleavage\r\nCSCE = Conformation sensitive capillary electrophoresis\r\nDGGE = Denaturing-Gradient Gel-Electrophoresis\r\nDHPLC = Denaturing High-Performance Liquid Chromatography\r\nDOVAM = Detection Of Virtually All Mutations (SSCA variant)\r\nDSCA = Double-Strand DNA Conformation Analysis\r\nHD = HeteroDuplex analysis\r\nIHC = Immuno-Histo-Chemistry\r\nmPCR = multiplex PCR\r\nMAPH = Multiplex Amplifiable Probe Hybridisation\r\nMLPA = Multiplex Ligation-dependent Probe Amplification\r\nNGS = Next Generation Sequencing\r\nPAGE = Poly-Acrylamide Gel-Electrophoresis\r\nPCR = Polymerase Chain Reaction\r\nPTT = Protein Truncation Test\r\nRT-PCR = Reverse Transcription and PCR\r\nSEQ = SEQuencing\r\nSouthern = Southern Blotting\r\nSSCA = Single-Strand DNA Conformation Analysis (SSCP)\r\nWestern = Western Blotting" WHERE id = "Screening/Technique"',
                                'UPDATE ' . TABLE_COLS . ' SET mandatory = 1 WHERE id IN ("VariantOnTranscript/RNA", "VariantOnTranscript/Protein")',
                                'UPDATE ' . TABLE_SHARED_COLS . ' SET mandatory = 1 WHERE colid IN ("VariantOnTranscript/RNA", "VariantOnTranscript/Protein")',
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD COLUMN panelid MEDIUMINT(8) UNSIGNED ZEROFILL AFTER id',
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD CONSTRAINT ' . TABLE_INDIVIDUALS . '_fk_panelid FOREIGN KEY (panelid) REFERENCES ' . TABLE_INDIVIDUALS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD COLUMN panel_size MEDIUMINT UNSIGNED NOT NULL DEFAULT 1 AFTER panelid',
                                'DELETE FROM ' . TABLE_COLS . ' WHERE id = "VariantOnTranscript/DBID"',
                                'UPDATE ' . TABLE_COLS . ' SET description_form = "This ID is used to group multiple instances of the same variant together. The ID starts with the gene symbol of the transcript most influenced by the variant or otherwise the closest gene, followed by an underscore (_) and the ID code, usually six digits.", preg_pattern = "/^[A-Z][A-Z0-9]+_[0-9]{6}\\\\b/" WHERE id = "VariantOnGenome/DBID"',
                                'ALTER TABLE ' . TABLE_USERS . ' MODIFY COLUMN password CHAR(50) NOT NULL',
                                'ALTER TABLE ' . TABLE_USERS . ' MODIFY COLUMN password_autogen CHAR(50)',
                                'ALTER TABLE ' . TABLE_USERS . ' DROP COLUMN current_db',
                              ),
                    '3.0-alpha-03' =>
                         array(
                                'UPDATE ' . TABLE_SOURCES . ' SET url = "http://www.omim.org/entry/{{ ID }}" WHERE id = "omim" AND url = "http://www.ncbi.nlm.nih.gov/omim/{{ ID }}"',
                                'UPDATE ' . TABLE_LINKS . ' SET replace_text = "<A href=\"http://www.omim.org/entry/[1]#[2]\" target=\"_blank\">(OMIM [2])</A>" WHERE id = 4 AND replace_text = "<A href=\"http://www.ncbi.nlm.nih.gov/omim/[1]#[1]Variants[2]\" target=\"_blank\">(OMIM [2])</A>"',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' ADD COLUMN statusid TINYINT(1) UNSIGNED AFTER ownerid',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' ADD INDEX (statusid)',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' ADD CONSTRAINT ' . TABLE_PHENOTYPES . '_fk_statusid FOREIGN KEY (statusid) REFERENCES ' . TABLE_DATA_STATUS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',
                                'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' DROP COLUMN edited_date',
                                'UPDATE ' . TABLE_COLS . ' SET form_type = "ID||text|15" WHERE id = "VariantOnGenome/DBID" AND form_type = "ID||text|40"',
                              ),
                    '3.0-alpha-04' =>
                         array(
                                'DELETE FROM ' . TABLE_DATA_STATUS . ' WHERE id IN (' . STATUS_IN_PROGRESS . ', ' . STATUS_PENDING . ')',
                                'INSERT INTO ' . TABLE_DATA_STATUS . ' VALUES (' . STATUS_IN_PROGRESS . ', "In progress")',
                                'INSERT INTO ' . TABLE_DATA_STATUS . ' VALUES (' . STATUS_PENDING . ', "Pending")',
                                'UPDATE ' . TABLE_COLS . ' SET description_form = "This ID is used to group multiple instances of the same variant together. The ID starts with the gene symbol of the transcript most influenced by the variant or otherwise the closest gene, followed by an underscore (_) and the ID code, which consists of six digits." WHERE id = "VariantOnGenome/DBID" AND description_form = "This ID is used to group multiple instances of the same variant together. The ID starts with the gene symbol of the transcript most influenced by the variant or otherwise the closest gene, followed by an underscore (_) and the ID code, usually six digits."',
                              ),
                    '3.0-alpha-05' =>
                         array(
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' DROP FOREIGN KEY ' . TABLE_INDIVIDUALS . '_fk_ownerid',
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' DROP KEY ownerid',
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' CHANGE ownerid owned_by SMALLINT(5) UNSIGNED ZEROFILL',
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD INDEX (owned_by)',
                                'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD CONSTRAINT ' . TABLE_INDIVIDUALS . '_fk_owned_by FOREIGN KEY (owned_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',

                                'ALTER TABLE ' . TABLE_VARIANTS . ' DROP FOREIGN KEY ' . TABLE_VARIANTS . '_fk_ownerid',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' DROP KEY ownerid',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' CHANGE ownerid owned_by SMALLINT(5) UNSIGNED ZEROFILL',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' ADD INDEX (owned_by)',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' ADD CONSTRAINT ' . TABLE_VARIANTS . '_fk_owned_by FOREIGN KEY (owned_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',

                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' DROP FOREIGN KEY ' . TABLE_PHENOTYPES . '_fk_ownerid',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' DROP KEY ownerid',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' CHANGE ownerid owned_by SMALLINT(5) UNSIGNED ZEROFILL',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' ADD INDEX (owned_by)',
                                'ALTER TABLE ' . TABLE_PHENOTYPES . ' ADD CONSTRAINT ' . TABLE_PHENOTYPES . '_fk_owned_by FOREIGN KEY (owned_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',

                                'ALTER TABLE ' . TABLE_SCREENINGS . ' DROP FOREIGN KEY ' . TABLE_SCREENINGS . '_fk_ownerid',
                                'ALTER TABLE ' . TABLE_SCREENINGS . ' DROP KEY ownerid',
                                'ALTER TABLE ' . TABLE_SCREENINGS . ' CHANGE ownerid owned_by SMALLINT(5) UNSIGNED ZEROFILL',
                                'ALTER TABLE ' . TABLE_SCREENINGS . ' ADD INDEX (owned_by)',
                                'ALTER TABLE ' . TABLE_SCREENINGS . ' ADD CONSTRAINT ' . TABLE_SCREENINGS . '_fk_owned_by FOREIGN KEY (owned_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',
                              ),
                    '3.0-alpha-07' =>
                        array(
                                'ALTER TABLE ' . TABLE_TRANSCRIPTS . ' ADD COLUMN id_mutalyzer TINYINT(3) UNSIGNED ZEROFILL AFTER name',
                                'ALTER TABLE ' . TABLE_SCREENINGS . ' ADD COLUMN variants_found BOOLEAN NOT NULL DEFAULT 1 AFTER individualid',

                                'ALTER TABLE ' . TABLE_VARIANTS . ' DROP FOREIGN KEY ' . TABLE_VARIANTS . '_fk_pathogenicid',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' DROP KEY pathogenicid',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' CHANGE pathogenicid effectid TINYINT(2) UNSIGNED ZEROFILL',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' ADD INDEX (effectid)',

                                'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' DROP FOREIGN KEY ' . TABLE_VARIANTS_ON_TRANSCRIPTS . '_fk_pathogenicid',
                                'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' DROP KEY pathogenicid',
                                'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' CHANGE pathogenicid effectid TINYINT(2) UNSIGNED ZEROFILL',
                                'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' ADD INDEX (effectid)',

                                'RENAME TABLE ' . TABLEPREFIX . '_variant_pathogenicity TO ' . TABLE_EFFECT,
                                'ALTER TABLE ' . TABLE_VARIANTS . ' ADD CONSTRAINT ' . TABLE_VARIANTS . '_fk_effectid FOREIGN KEY (effectid) REFERENCES ' . TABLE_EFFECT . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',
                                'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' ADD CONSTRAINT ' . TABLE_VARIANTS_ON_TRANSCRIPTS . '_fk_effectid FOREIGN KEY (effectid) REFERENCES ' . TABLE_EFFECT . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',
                                'UPDATE ' . TABLE_VARIANTS . ' SET effectid = 55 WHERE effectid < 11 OR effectid IS NULL',
                                'UPDATE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' SET effectid = 55 WHERE effectid < 11 OR effectid IS NULL',

                                'UPDATE ' . TABLE_LINKS . ' SET replace_text = "<A href=\"http://www.ncbi.nlm.nih.gov/SNP/snp_ref.cgi?rs=[1]\" target=\"_blank\">dbSNP</A>" WHERE id = 2 AND replace_text = "<A href=\"http://www.ncbi.nlm.nih.gov/SNP/snp_ref.cgi?type=rs&amp;rs=rs[1]\" target=\"_blank\">dbSNP</A>"',
                             ),
                    '3.0-alpha-07b' =>
                        array(
                                'UPDATE ' . TABLE_COLS . ' SET form_type = "ID|This ID is used to group multiple instances of the same variant together. The ID starts with the gene symbol of the transcript most influenced by the variant or otherwise the closest gene, followed by an underscore (_) and the 6 digit ID code.|text|20" WHERE id = "VariantOnGenome/DBID"',
                                'UPDATE ' . TABLE_COLS . ' SET description_form = "NOTE: This field will be predicted and filled in by LOVD, if left empty." WHERE id = "VariantOnGenome/DBID"',
                                'UPDATE ' . TABLE_COLS . ' SET preg_pattern = "/^(chr(\\\\d{1,2}|[XYM])|(C(\\\\d{1,2}|[XYM])orf\\\\d+-|[A-Z][A-Z0-9]+-)?(C(\\\\d{1,2}|[XYM])orf\\\\d+|[A-Z][A-Z0-9]+))_[0-9]{6}\\\\b/" WHERE id = "VariantOnGenome/DBID"',
                                'UPDATE ' . TABLE_COLS . ' SET description_legend_short = REPLACE(description_legend_short, "Database", "DataBase"), description_legend_full = REPLACE(description_legend_full, "Database", "DataBase") WHERE id = "VariantOnGenome/DBID"',
                                'INSERT INTO ' . TABLE_USERS . '(name, created_date) VALUES("LOVD", NOW())',
                                'UPDATE ' . TABLE_USERS . ' SET id = 0, created_by = 0 WHERE username = ""',
                             ),
                    '3.0-alpha-07c' =>
                        array(
                                'ALTER TABLE ' . TABLE_VARIANTS . ' ADD COLUMN mapping_flags TINYINT(3) UNSIGNED NOT NULL AFTER type',
                                'ALTER TABLE ' . TABLE_USERS . ' AUTO_INCREMENT = 1',
                                'UPDATE ' . TABLE_COLS . ' SET edited_by = 0 WHERE id = "VariantOnGenome/DBID"',
                                'UPDATE ' . TABLE_COLS . ' SET width = 80 WHERE id = "VariantOnGenome/DBID"',
                             ),
                    '3.0-alpha-07d' =>
                        array(
                                'ALTER TABLE ' . TABLE_GENES . ' MODIFY COLUMN chromosome VARCHAR(2)',
                                'ALTER TABLE ' . TABLE_GENES . ' ADD INDEX (chromosome)',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' MODIFY COLUMN chromosome VARCHAR(2)',
                                'CREATE TABLE ' . TABLE_CHROMOSOMES . ' (name VARCHAR(2) NOT NULL, sort_id TINYINT(3) UNSIGNED NOT NULL, hg18_id_ncbi VARCHAR(20) NOT NULL, hg19_id_ncbi VARCHAR(20) NOT NULL, PRIMARY KEY (name)) ENGINE=InnoDB, DEFAULT CHARACTER SET utf8',
                'chr_values' => 'Reserved for the insert query of the new chromosome table. This will be added later in this script.',
                                'ALTER TABLE ' . TABLE_GENES . ' ADD CONSTRAINT ' . TABLE_GENES . '_fk_chromosome FOREIGN KEY (chromosome) REFERENCES ' . TABLE_CHROMOSOMES . ' (name) ON DELETE SET NULL ON UPDATE CASCADE',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' ADD CONSTRAINT ' . TABLE_VARIANTS . '_fk_chromosome FOREIGN KEY (chromosome) REFERENCES ' . TABLE_CHROMOSOMES . ' (name) ON DELETE SET NULL ON UPDATE CASCADE',
                             ),
                    '3.0-beta-02' =>
                        array(
                                'UPDATE ' . TABLE_COLS . ' SET form_type = "Frequency||text|10" WHERE id = "VariantOnGenome/Frequency" AND form_type = "Frequency||text|15"',
                                'ALTER TABLE ' . TABLE_TRANSCRIPTS . ' ADD UNIQUE (id_ncbi)',
                             ),
                    '3.0-beta-02b' =>
                        array(
                                'ALTER TABLE ' . TABLE_CONFIG . ' ADD COLUMN proxy_host VARCHAR(255) NOT NULL AFTER refseq_build',
                                'ALTER TABLE ' . TABLE_CONFIG . ' ADD COLUMN proxy_port SMALLINT(5) UNSIGNED AFTER proxy_host',
                             ),
                    '3.0-beta-02c' =>
                        array(
                                'ALTER TABLE ' . TABLE_GENES . ' ADD COLUMN imprinting VARCHAR(10) NOT NULL DEFAULT "unknown" AFTER chrom_band',
                             ),
                    '3.0-beta-02d' =>
                        array(
                                'INSERT INTO ' . TABLE_COLS . ' VALUES ("VariantOnGenome/Conservation_score/GERP",      4, 100, 0, 0, 0, "GERP conservation",    "", "Conservation score as calculated by GERP.", "The Conservation score as calculated by GERP.", "DECIMAL(5,3)", "GERP conservation score||text|6", "", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT INTO ' . TABLE_COLS . ' VALUES ("VariantOnGenome/dbSNP",                        8, 120, 0, 0, 0, "dbSNP ID",             "", "The dbSNP ID.", "The dbSNP ID.", "VARCHAR(15)", "dbSNP ID|If available, please fill in the dbSNP ID, such as rs12345678.|text|10", "", "/^[rs]s\\\\d+$/", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT INTO ' . TABLE_COLS . ' VALUES ("VariantOnTranscript/Distance_to_splice_site", 10, 150, 0, 0, 0, "Splice distance",      "", "The distance to the nearest splice site.", "The distance to the nearest splice site.", "MEDIUMINT(8) UNSIGNED", "Distance to splice site||text|8", "", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT INTO ' . TABLE_COLS . ' VALUES ("VariantOnTranscript/GVS/Function",             9, 200, 0, 0, 0, "GVS function",         "", "Functional annotation of this position by GVS.", "The functional annotation of this position from the Genome Variation Server.", "VARCHAR(30)", "GVS function|Whether the variant is missense, nonsense, in an intron, UTR, etc.|text|30", "", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT INTO ' . TABLE_COLS . ' VALUES ("VariantOnTranscript/PolyPhen",                 8, 200, 0, 0, 0, "PolyPhen prediction",  "", "The effect predicted by PolyPhen.", "The effect predicted by PolyPhen.", "VARCHAR(20)", "PolyPhen prediction||select|1|true|false|false", "benign = Benign\r\npossiblyDamaging = Possably damaging\r\nprobablyDamaging = Probably damaging\r\nnoPrediction = No prediction", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT INTO ' . TABLE_COLS . ' VALUES ("VariantOnTranscript/Position",                 5, 100, 0, 0, 0, "Position",             "", "Position in cDNA sequence.", "The position of this variant in the cDNA sequence.", "MEDIUMINT(5)", "cDNA Position||text|5", "", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                             ),
                    '3.0-beta-03b' =>
                        array(
                                'UPDATE ' . TABLE_LINKS . ' SET description = CONCAT(description, "\r\n\r\nExamples:\r\n{PMID:Fokkema et. al.:15977173}\r\n{PMID:Fokkema et. al.:21520333}") WHERE name = "PubMed" AND description = "Links to abstracts in the PubMed database.\r\n[1] = The name of the author(s).\r\n[2] = The PubMed ID."',
                                'UPDATE ' . TABLE_LINKS . ' SET description = CONCAT(description, "\r\n\r\nExamples:\r\n{dbSNP:rs193143796}\r\n{dbSNP:193143796}") WHERE name = "DbSNP" AND description = "Links to the DbSNP database.\r\n[1] = The DbSNP ID."',
                                'UPDATE ' . TABLE_LINKS . ' SET description = CONCAT(description, "\r\n\r\nExamples:\r\n{GenBank:NG_012232.1}\r\n{GenBank:NC_000001.10}") WHERE name = "GenBank" AND description = "Links to GenBank sequences.\r\n[1] = The GenBank ID."',
                                'UPDATE ' . TABLE_LINKS . ' SET description = CONCAT(description, "\r\n\r\nExamples:\r\n{OMIM:300377:0021}\r\n{OMIM:188840:0003}") WHERE name = "OMIM" AND description = "Links to an allelic variant on the gene\'s OMIM page.\r\n[1] = The OMIM gene ID.\r\n[2] = The number of the OMIM allelic variant on that page."',
                                // This should cascade to ACTIVE_COLS and SHARED_COLS.
                                'UPDATE ' . TABLE_COLS . ' SET id = "VariantOnGenome/Published_as", head_column = "Published as", form_type = REPLACE(form_type, "DNA published", "Published as") WHERE id = "VariantOnGenome/DNA_published"',
                                'UPDATE ' . TABLE_COLS . ' SET id = "VariantOnTranscript/Published_as", head_column = "Published as", form_type = REPLACE(form_type, "DNA published", "Published as") WHERE id = "VariantOnTranscript/DNA_published"',
                             ),
                    '3.0-beta-03c' =>
                        array(
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Individual/Consanguinity",           249,  40, 0, 0, 0, "Consanguinity",        "Indicates whether the parents are related (consanguineous), not related (non-consanguineous) or whether consanguinity is not known (unknown)", "Indicates whether the parents are related (consanguineous), not related (non-consanguineous) or whether consanguinity is not known (unknown)", "Indicates whether the parents are related (consanguineous), not related (non-consanguineous) or whether consanguinity is not known (unknown)", "VARCHAR(5)", "Consanguinity||select|1|--Not specified--|false|false", "? = Unknown\r\nno = Non-consanguineous parents\r\nyes = Consanguineous parents", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Phenotype/Date",                     255,  80, 0, 0, 0, "Date",                 "Format: YYYY-MM-DD.", "Date the phenotype was observed.", "Date the phenotype was observed, in YYYY-MM-DD format.", "DATE", "Date||text|10", "", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Phenotype/Inheritance",              254, 200, 0, 0, 0, "Inheritance",          "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial) or isolated (sporadic)", "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial) or isolated (sporadic)", "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial) or isolated (sporadic)", "VARCHAR(25)", "Inheritance||select|1|--Not specified--|false|false", "Unknown\r\nFamilial\r\nFamilial, autosomal dominant\r\nFamilial, autosomal recessive\r\nFamilial, X-linked dominant\r\nFamilial, X-linked dominant, male sparing\r\nFamilial, X-linked recessive\r\nPaternal, Y-linked\r\nMaternal, mitochondrial\r\nIsolated (sporadic)", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("VariantOnGenome/Genetic_origin",      11, 200, 0, 0, 0, "Genetic origin",       "Origin of variant; unknown, germline (i.e. inherited), somatic, de novo, from parental disomy (maternal or paternal) or in vitro (cloned)", "Origin of variant; unknown, germline (i.e. inherited), somatic, de novo, from parental disomy (maternal or paternal) or in vitro (cloned)", "Origin of variant; unknown, germline (i.e. inherited), somatic, de novo, from parental disomy (maternal or paternal) or in vitro (cloned)", "VARCHAR(40)", "Genetic origin||select|1|--Not specified--|false|false", "Unknown\r\n\r\nGermline (inherited)\r\nSomatic\r\nDe novo\r\nUniparental disomy\r\nUniparental disomy, maternal allele\r\nUniparental disomy, paternal allele", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("VariantOnGenome/Segregation",         12,  40, 0, 0, 0, "Segregation",          "Indicates whether the variant segregates with the disease (yes), does not segregate with the disease (no) or segregation is unknown (?)", "Indicates whether the variant segregates with the disease (yes), does not segregate with the disease (no) or segregation is unknown (?)", "Indicates whether the variant segregates with the disease (yes), does not segregate with the disease (no) or segregation is unknown (?)", "VARCHAR(5)", "Segregation||select|1|--Not specified--|false|false", "? = Unknown\r\nyes = Segregates with disease\r\nno = Does not segregate with disease", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'ALTER TABLE ' . TABLE_GENES . ' ADD INDEX (id_hgnc)',
                             ),
                    '3.0-beta-03d' =>
                        array(
                                'ALTER TABLE ' . TABLE_EFFECT . ' MODIFY COLUMN id TINYINT(2) UNSIGNED',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' MODIFY COLUMN effectid TINYINT(2) UNSIGNED',
                                'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' MODIFY COLUMN effectid TINYINT(2) UNSIGNED',
                                'CREATE TABLE ' . TABLE_ALLELES . ' (id TINYINT(2) UNSIGNED NOT NULL, name VARCHAR(20) NOT NULL, display_order TINYINT(1) UNSIGNED NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB, DEFAULT CHARACTER SET utf8',
             'allele_values' => 'Reserved for the insert query of the new allele table. This will be added later in this script.',
                                'ALTER TABLE ' . TABLE_VARIANTS . ' ADD CONSTRAINT ' . TABLE_VARIANTS . '_fk_allele FOREIGN KEY (allele) REFERENCES ' . TABLE_ALLELES . ' (id) ON UPDATE CASCADE',
                                'UPDATE ' . TABLE_COLS . ' SET preg_pattern = "/^(chr(\\\\d{1,2}|[XYM])|(C(\\\\d{1,2}|[XYM])orf\\\\d+-|[A-Z][A-Z0-9]+-)?(C(\\\\d{1,2}|[XYM])orf\\\\d+|[A-Z][A-Z0-9]+))_[0-9]{6}$/" WHERE id = "VariantOnGenome/DBID"',
                             ),
                    '3.0-beta-04' =>
                        array(
                                'ALTER TABLE ' . TABLE_GENES . ' DROP INDEX id_hgnc',
                                'ALTER TABLE ' . TABLE_GENES . ' ADD UNIQUE (id_hgnc)',
                                'ALTER TABLE ' . TABLE_EFFECT . ' MODIFY COLUMN id TINYINT(2) UNSIGNED NOT NULL',
                                'UPDATE ' . TABLE_CONFIG . ' SET proxy_port = NULL WHERE proxy_port = 0',
                             ),
                    '3.0-beta-05' =>
                        array(
                                'UPDATE ' . TABLE_COLS . ' SET mysql_type = "VARCHAR(50)" WHERE id = "Phenotype/Inheritance"',
                                'DELETE FROM ' . TABLE_COLS2LINKS . ' WHERE colid IN (SELECT id FROM ' . TABLE_COLS . ' WHERE mysql_type NOT REGEXP "^(VARCHAR|TEXT)" OR id = "VariantOnGenome/DBID")',
                                'UPDATE ' . TABLE_COLS . ' SET preg_pattern = "/^[rs]s\\\\d+$/" WHERE id = "VariantOnGenome/dbSNP"',
                                'UPDATE ' . TABLE_COLS . ' SET preg_pattern = "/^(chr(\\\\d{1,2}|[XYM])|(C(\\\\d{1,2}|[XYM])orf\\\\d+-|[A-Z][A-Z0-9]+-)?(C(\\\\d{1,2}|[XYM])orf\\\\d+|[A-Z][A-Z0-9]+))_\\\\d{6}$/" WHERE id = "VariantOnGenome/DBID"',
                             ),
                    '3.0-beta-06' =>
                        array(
                                'UPDATE ' . TABLE_COLS . ' SET col_order = 240 WHERE id = "Individual/Consanguinity" AND col_order = 249',
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Individual/Death/Cause",  249, 150, 0, 0, 0, "Cause of death", "", "The cause of the individual\'s death, if known and applicable.", "The cause of the individual\'s death, if known and applicable.", "VARCHAR(255)", "Cause of death|The cause of the individual\'s death, if known and applicable.|text|30", "", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Individual/Age_of_death", 248, 100, 0, 0, 0, "Age of death", "Type 35y for 35 years, 04y08m for 4 years and 8 months, 18y? for around 18 years, >54y for still alive at 55, ? for unknown.", "The age at which the individual deceased, if known and applicable. 04y08m = 4 years and 8 months.", "The age at which the individual deceased, if known and applicable.\r\n<UL style=\"margin-top:0px;\">\r\n  <LI>35y = 35 years</LI>\r\n  <LI>04y08m = 4 years and 8 months</LI>\r\n  <LI>18y? = around 18 years</LI>\r\n  <LI>&gt;54y = still alive at 55</LI>\r\n  <LI>? = unknown</LI>\r\n</UL>", "VARCHAR(12)", "Age of death|The age at which the individual deceased, if known and applicable. Numbers lower than 10 should be prefixed by a zero and the field should always begin with years, to facilitate sorting on this column.|text|10", "", "/^([<>]?\\\\d{2,3}y(\\\\d{2}m(\\\\d{2}d)?)?)?\\\\??$/", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'UPDATE ' . TABLE_COLS . ' SET col_order = 1 WHERE id = "Phenotype/Date" AND col_order = 255',
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Phenotype/Age",             2, 100, 0, 0, 0, "Age examined", "Type 35y for 35 years, 04y08m for 4 years and 8 months, 18y? for around 18 years, >54y for older than 54, ? for unknown.", "The age at which the individual was examined, if known. 04y08m = 4 years and 8 months.", "The age at which the individual was examined, if known.\r\n<UL style=\"margin-top:0px;\">\r\n  <LI>35y = 35 years</LI>\r\n  <LI>04y08m = 4 years and 8 months</LI>\r\n  <LI>18y? = around 18 years</LI>\r\n  <LI>&gt;54y = older than 54</LI>\r\n  <LI>? = unknown</LI>\r\n</UL>", "VARCHAR(12)", "Age at examination|The age at which the individual was examined, if known. Numbers lower than 10 should be prefixed by a zero and the field should always begin with years, to facilitate sorting on this column.|text|10", "", "/^([<>]?\\\\d{2,3}y(\\\\d{2}m(\\\\d{2}d)?)?)?\\\\??$/", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Phenotype/Length",        200, 100, 0, 0, 0, "Length", "", "Length of the individual, in cm.", "Length of the individual, in centimeters (cm).", "SMALLINT(3) UNSIGNED", "Length of individual (cm)|Length of individual, in centimeters.|text|3", "", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                                'UPDATE ' . TABLE_LINKS . ' SET description = "Links to abstracts in the PubMed database.\r\n[1] = The name of the author(s), possibly including year.\r\n[2] = The PubMed ID.\r\n\r\nExample:\r\n{PMID:Fokkema et al. (2011):21520333}" WHERE id = 1 AND name = "PubMed"',
                                'UPDATE ' . TABLE_COLS . ' SET head_column = "DNA change (genomic)" WHERE id = "VariantOnGenome/DNA"',
                                'UPDATE ' . TABLE_COLS . ' SET head_column = "DNA change (cDNA)" WHERE id = "VariantOnTranscript/DNA"',
                                'UPDATE ' . TABLE_COLS . ' SET select_options = "Unknown\r\nGermline (inherited)\r\nSomatic\r\nDe novo\r\nUniparental disomy\r\nUniparental disomy, maternal allele\r\nUniparental disomy, paternal allele" WHERE id = "VariantOnGenome/Genetic_origin"',
                                // Delete all transcript positions from TABLE_TRANSCRIPTS, so that they can be recalculated.
                                'UPDATE ' . TABLE_TRANSCRIPTS . ' SET position_c_mrna_start = 0, position_c_mrna_end = 0, position_c_cds_end = 0, position_g_mrna_start = 0, position_g_mrna_end = 0',
                        ),
                    '3.0-beta-07' =>
                        array(
                            'ALTER TABLE ' . TABLE_COLS . ' MODIFY COLUMN form_type TEXT NOT NULL',
                            'UPDATE ' . TABLE_COLS . ' SET select_options = "? = Unknown\r\nF = Female\r\nM = Male\r\nrF = Raised as female\r\nrM = Raised as male" WHERE id = "Individual/Gender" and select_options = "Female\r\nMale\r\nUnknown"',
                            'UPDATE ' . TABLE_COLS . ' SET form_type = "Geographic origin|If mixed, please indicate origin of father and mother, if known.|text|30" WHERE id = "Individual/Origin/Geographic" and select_options = "Geographic origin||text|30"',
                            'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Phenotype/Additional", 250, 200, 0, 1, 0, "Phenotype details", "Additional information on the phenotype of the individual.", "Additional information on the phenotype of the individual.", "Additional information on the phenotype of the individual.", "TEXT", "Additional phenotype details||textarea|40|4", "", "", 1, 1, 1, 0, NOW(), NULL, NULL)',
                            'UPDATE ' . TABLE_COLS . ' SET hgvs = 1, standard = 1 WHERE id = "Phenotype/Inheritance"',
                            'UPDATE ' . TABLE_COLS . ' SET select_options = "? = Unknown\r\narrayCGH = array for Comparative Genomic Hybridisation\r\narraySEQ = array for resequencing\r\narraySNP = array for SNP typing\r\narrayCNV = array for Copy Number Variation (SNP and CNV probes)\r\nBESS = Base Excision Sequence Scanning\r\nCMC = Chemical Mismatch Cleavage\r\nCSCE = Conformation Sensitive Capillary Electrophoresis\r\nDGGE = Denaturing-Gradient Gel-Electrophoresis\r\nDHPLC = Denaturing High-Performance Liquid Chromatography\r\nDOVAM = Detection Of Virtually All Mutations (SSCA variant)\r\nddF = dideoxy Fingerprinting\r\nDSCA = Double-Strand DNA Conformation Analysis\r\nEMC = Enzymatic Mismatch Cleavage\r\nHD = HeteroDuplex analysis\r\nMCA = high-resolution Melting Curve Analysis (hrMCA)\r\nIHC = Immuno-Histo-Chemistry\r\nMAPH = Multiplex Amplifiable Probe Hybridisation\r\nMLPA = Multiplex Ligation-dependent Probe Amplification\r\nSEQ-NG = Next-Generation Sequencing\r\nSEQ-NG-H = Next-Generation Sequencing - Helicos\r\nSEQ-NG-I = Next-Generation Sequencing - Illumina/Solexa\r\nSEQ-NG-R = Next-Generation Sequencing - Roche/454\r\nSEQ-NG-S = Next-Generation Sequencing - SOLiD\r\nNorthern = Northern blotting\r\nPCR = Polymerase Chain Reaction\r\nPCRdig = PCR + restriction enzyme digestion\r\nPCRlr = PCR, long-range\r\nPCRm = PCR, multiplex\r\nPCRq = PCR, quantitative\r\nPAGE = Poly-Acrylamide Gel-Electrophoresis\r\nPTT = Protein Truncation Test\r\nPFGE = Pulsed-Field Gel-Electrophoresis (+Southern)\r\nRT-PCR = Reverse Transcription and PCR\r\nSEQ = SEQuencing\r\nSBE = Single Base Extension\r\nSSCA = Single-Strand DNA Conformation polymorphism Analysis (SSCP)\r\nSSCAf = SSCA, fluorescent (SSCP)\r\nSouthern = Southern blotting\r\nTaqMan = TaqMan assay\r\nWestern = Western Blotting" WHERE id = "Screening/Technique" and select_options = "Unknown\r\nBESS = Base Excision Sequence Scanning\r\nCMC = Chemical Mismatch Cleavage\r\nCSCE = Conformation sensitive capillary electrophoresis\r\nDGGE = Denaturing-Gradient Gel-Electrophoresis\r\nDHPLC = Denaturing High-Performance Liquid Chromatography\r\nDOVAM = Detection Of Virtually All Mutations (SSCA variant)\r\nDSCA = Double-Strand DNA Conformation Analysis\r\nHD = HeteroDuplex analysis\r\nIHC = Immuno-Histo-Chemistry\r\nmPCR = multiplex PCR\r\nMAPH = Multiplex Amplifiable Probe Hybridisation\r\nMLPA = Multiplex Ligation-dependent Probe Amplification\r\nNGS = Next Generation Sequencing\r\nPAGE = Poly-Acrylamide Gel-Electrophoresis\r\nPCR = Polymerase Chain Reaction\r\nPTT = Protein Truncation Test\r\nRT-PCR = Reverse Transcription and PCR\r\nSEQ = SEQuencing\r\nSouthern = Southern Blotting\r\nSSCA = Single-Strand DNA Conformation Analysis (SSCP)\r\nWestern = Western Blotting"',
                            'UPDATE ' . TABLE_COLS . ' SET select_options = "DNA\r\nRNA = RNA (cDNA)\r\nProtein\r\n? = unknown" WHERE id = "Screening/Template" and select_options = "DNA\r\nRNA\r\nProtein"',
                            'UPDATE ' . TABLE_COLS . ' SET mandatory = 1 WHERE id = "VariantOnGenome/Genetic_origin"',
                            'UPDATE ' . TABLE_COLS . ' SET form_type = "GVS function||select|1|true|false|false", select_options = "intergenic\r\nnear-gene-5\r\nutr-5\r\ncoding\r\ncoding-near-splice\r\ncodingComplex\r\ncodingComplex-near-splice\r\nframeshift\r\nframeshift-near-splice\r\nsplice-5\r\nintron\r\nsplice-3\r\nutr-3\r\nnear-gene-3" WHERE id = "VariantOnTranscript/GVS/Function" and form_type LIKE "GVS function|%|text|%"',
                            'UPDATE ' . TABLE_COLS . ' SET form_type = "Protein change (HGVS format)|Description of variant at protein level (following HGVS recommendations); e.g. p.(Arg345Pro) = change predicted from DNA (RNA not analysed), p.Arg345Pro = change derived from RNA analysis, p.0 (no protein produced), p.? (unknown effect).|text|30" WHERE id = "VariantOnTranscript/Protein" and form_type LIKE "Protein change (HGVS format)||text|%"',
                            'UPDATE ' . TABLE_COLS . ' SET form_type = "RNA change (HGVS format)|Description of variant at RNA level (following HGVS recommendations); e.g. r.123c>u, r.? = unknown, r.(?) = RNA not analysed but probably transcribed copy of DNA variant, r.spl? = RNA not analysed but variant probably affects splicing, r.(spl?) = RNA not analysed but variant may affect splicing.|text|30" WHERE id = "VariantOnTranscript/RNA" and form_type LIKE "RNA change (HGVS format)||text|%"',
                            'INSERT INTO ' . TABLE_DISEASES . ' (symbol, name, created_by, created_date) VALUES ("Healthy/Control", "Healthy individual / control", 0, NOW())',
                            'UPDATE ' . TABLE_DISEASES . ' SET id = 0 WHERE id_omim IS NULL AND created_by = 0 AND symbol = "Healthy/Control"',
                        ),
                    '3.0-beta-08' =>
                        array(
                            'UPDATE ' . TABLE_COLS . ' SET width = 120 WHERE id = "VariantOnGenome/DBID" AND width < 120',
                            'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD COLUMN fatherid MEDIUMINT(8) UNSIGNED ZEROFILL AFTER id',
                            'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD COLUMN motherid MEDIUMINT(8) UNSIGNED ZEROFILL AFTER fatherid',
                            'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD INDEX (fatherid)',
                            'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD INDEX (motherid)',
                            'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD CONSTRAINT ' . TABLE_INDIVIDUALS . '_fk_fatherid FOREIGN KEY (fatherid) REFERENCES ' . TABLE_INDIVIDUALS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',
                            'ALTER TABLE ' . TABLE_INDIVIDUALS . ' ADD CONSTRAINT ' . TABLE_INDIVIDUALS . '_fk_motherid FOREIGN KEY (motherid) REFERENCES ' . TABLE_INDIVIDUALS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE',
                            'ALTER TABLE ' . TABLE_CONFIG . ' ADD COLUMN omim_apikey VARCHAR(40) NOT NULL AFTER mutalyzer_soap_url',
                            'ALTER TABLE ' . TABLE_CONFIG . ' ADD COLUMN proxy_username VARCHAR(255) NOT NULL AFTER proxy_port',
                            'ALTER TABLE ' . TABLE_CONFIG . ' ADD COLUMN proxy_password VARCHAR(255) NOT NULL AFTER proxy_username',
                        ),
                    '3.0-beta-09' =>
                        array(
                            'UPDATE ' . TABLE_CONFIG . ' SET logo_uri = "gfx/LOVD3_logo145x50.jpg" WHERE logo_uri = "gfx/LOVD_logo130x50.jpg"',
                        ),
                    '3.0-beta-09b' =>
                        array(
                            'ALTER TABLE ' . TABLE_USERS . ' ADD COLUMN orcid_id CHAR(19) AFTER id',
                            'ALTER TABLE ' . TABLE_USERS . ' ADD UNIQUE (orcid_id)',
                        ),
                    '3.0-beta-09c' =>
                        array(
                            'UPDATE ' . TABLE_COLS . ' SET description_form         = "Indicates whether the variant segregates with the phenotype (yes), does not segregate with the phenotype (no) or segregation is unknown (?)" WHERE id = "VariantOnGenome/Segregation" AND description_form         = "Indicates whether the variant segregates with the disease (yes), does not segregate with the disease (no) or segregation is unknown (?)"',
                            'UPDATE ' . TABLE_COLS . ' SET description_legend_short = "Indicates whether the variant segregates with the phenotype (yes), does not segregate with the phenotype (no) or segregation is unknown (?)" WHERE id = "VariantOnGenome/Segregation" AND description_legend_short = "Indicates whether the variant segregates with the disease (yes), does not segregate with the disease (no) or segregation is unknown (?)"',
                            'UPDATE ' . TABLE_COLS . ' SET description_legend_full  = "Indicates whether the variant segregates with the phenotype (yes), does not segregate with the phenotype (no) or segregation is unknown (?)" WHERE id = "VariantOnGenome/Segregation" AND description_legend_full  = "Indicates whether the variant segregates with the disease (yes), does not segregate with the disease (no) or segregation is unknown (?)"',
                            'ALTER TABLE ' . TABLE_VARIANTS . ' MODIFY COLUMN mapping_flags TINYINT(3) UNSIGNED NOT NULL DEFAULT 0',
                        ),
                    '3.0-beta-09d' =>
                        array(
                            'INSERT IGNORE INTO ' . TABLE_LINKS . ' VALUES (NULL, "DOI", "{DOI:[1]:[2]}", "<A href=\"http://dx.doi.org/[2]\" target=\"_blank\">[1]</A>", "Links directly to an article using the DOI.\r\n[1] = The name of the author(s), possibly followed by the year of publication.\r\n[2] = The DOI.\r\n\r\nExample:\r\n{DOI:Fokkema et al. (2011):10.1002/humu.21438}", 0, NOW(), NULL, NULL)',
                            'INSERT IGNORE INTO ' . TABLE_COLS2LINKS . ' VALUES ("Individual/Reference", LAST_INSERT_ID())',
                            'UPDATE ' . TABLE_LINKS . ' SET description = "Links to abstracts in the PubMed database.\r\n[1] = The name of the author(s), possibly followed by the year of publication.\r\n[2] = The PubMed ID.\r\n\r\nExample:\r\n{PMID:Fokkema et al. (2011):21520333}", edited_by = 0, edited_date = NOW() WHERE id = 1 AND (description = "Links to abstracts in the PubMed database.\r\n[1] = The name of the author(s).\r\n[2] = The PubMed ID.\r\n\r\nExample:\r\n{PMID:Fokkema et al. (2011):21520333}" OR description = "Links to abstracts in the PubMed database.\r\n[1] = The name of the author(s), possibly including year.\r\n[2] = The PubMed ID.\r\n\r\nExample:\r\n{PMID:Fokkema et al. (2011):21520333}")',
                        ),
                    '3.0-beta-10b' =>
                        array(
                            'ALTER TABLE ' . TABLE_USERS . ' ADD COLUMN orcid_confirmed BOOLEAN NOT NULL DEFAULT 0 AFTER orcid_id',
                            'ALTER TABLE ' . TABLE_USERS . ' ADD COLUMN email_confirmed BOOLEAN NOT NULL DEFAULT 0 AFTER email',
                            'UPDATE ' . TABLE_COLS . ' SET preg_pattern = "/^(chr(\\\\d{1,2}|[XYM])|(C(\\\\d{1,2}|[XYM])orf[\\\\d][\\\\dA-Z]*-|[A-Z][A-Z0-9]+-)?(C(\\\\d{1,2}|[XYM])orf[\\\\d][\\\\dA-Z]*|[A-Z][A-Z0-9]+))_\\\\d{6}$/" WHERE preg_pattern = "/^(chr(\\\\d{1,2}|[XYM])|(C(\\\\d{1,2}|[XYM])orf\\\\d+-|[A-Z][A-Z0-9]+-)?(C(\\\\d{1,2}|[XYM])orf\\\\d+|[A-Z][A-Z0-9]+))_\\\\d{6}$/" AND id = "VariantOnGenome/DBID"',
                            'UPDATE ' . TABLE_COLS . ' SET form_type = "Genomic DNA change (HGVS format)|Description of variant at DNA level, based on the genomic DNA reference sequence (following HGVS recommendations); e.g. g.12345678C>T, g.12345678_12345890del, g.12345678_12345890dup.|text|30" WHERE form_type = "Genomic DNA change (HGVS format)||text|30" AND id = "VariantOnGenome/DNA"',
                        ),
                 '3.0-02' =>
                 array(
                     'INSERT IGNORE INTO ' . TABLE_COLS2LINKS . ' VALUES ("VariantOnGenome/Reference", 001)',
                 ),
                 '3.0-04' =>
                 array(
                     'INSERT IGNORE INTO ' . TABLE_COLS . ' VALUES ("Phenotype/Age/Onset", 1, 100, 0, 0, 0, "Age of onset", "Type 35y for 35 years, 04y08m for 4 years and 8 months, 18y? for around 18 years, >54y for older than 54, ? for unknown.", "The age at which the first symptoms of the disease appeared in the individual, if known. 04y08m = 4 years and 8 months.", "The age at which the first symptoms appeared in the individual, if known.\r\n<UL style=\"margin-top:0px;\">\r\n  <LI>35y = 35 years</LI>\r\n  <LI>04y08m = 4 years and 8 months</LI>\r\n  <LI>18y? = around 18 years</LI>\r\n  <LI>&gt;54y = older than 54</LI>\r\n  <LI>? = unknown</LI>\r\n</UL>", "VARCHAR(12)", "Age of onset|The age at which the first symptoms appeared in the individual, if known. Numbers lower than 10 should be prefixed by a zero and the field should always begin with years, to facilitate sorting on this column.|text|10", "", "/^([<>]?\\\\d{2,3}y(\\\\d{2}m(\\\\d{2}d)?)?)?\\\\??$/", 1, 1, 1, 0, NOW(), NULL, NULL)',
                     'UPDATE ' . TABLE_COLS . ' SET description_form = "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial), isolated (sporadic) or complex" WHERE description_form = "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial) or isolated (sporadic)" AND id = "Phenotype/Inheritance"',
                     'UPDATE ' . TABLE_COLS . ' SET description_legend_short = "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial), isolated (sporadic) or complex" WHERE description_legend_short = "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial) or isolated (sporadic)" AND id = "Phenotype/Inheritance"',
                     'UPDATE ' . TABLE_COLS . ' SET description_legend_full = "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial), isolated (sporadic) or complex" WHERE description_legend_full = "Indicates the inheritance of the phenotype in the family; unknown, familial (autosomal/X-linked, dominant/ recessive), paternal (Y-linked), maternal (mitochondrial) or isolated (sporadic)" AND id = "Phenotype/Inheritance"',
                     'UPDATE ' . TABLE_COLS . ' SET select_options = "Unknown\r\nFamilial\r\nFamilial, autosomal dominant\r\nFamilial, autosomal recessive\r\nFamilial, X-linked dominant\r\nFamilial, X-linked dominant, male sparing\r\nFamilial, X-linked recessive\r\nPaternal, Y-linked\r\nMaternal, mitochondrial\r\nIsolated (sporadic)\r\nComplex" WHERE select_options = "Unknown\r\nFamilial\r\nFamilial, autosomal dominant\r\nFamilial, autosomal recessive\r\nFamilial, X-linked dominant\r\nFamilial, X-linked dominant, male sparing\r\nFamilial, X-linked recessive\r\nPaternal, Y-linked\r\nMaternal, mitochondrial\r\nIsolated (sporadic)" AND id = "Phenotype/Inheritance"',
                 ),
                 '3.0-05' =>
                 array(
                     // I would expect these to fail if I don't remove the FKs first. But they don't. Apparently, VARCHARs are different than INT columns (see 3.0-14b update).
                     'ALTER TABLE ' . TABLE_GENES . ' MODIFY COLUMN id VARCHAR(25) NOT NULL',
                     'ALTER TABLE ' . TABLE_CURATES . ' MODIFY COLUMN geneid VARCHAR(25) NOT NULL',
                     'ALTER TABLE ' . TABLE_TRANSCRIPTS . ' MODIFY COLUMN geneid VARCHAR(25) NOT NULL',
                     'ALTER TABLE ' . TABLE_GEN2DIS . ' MODIFY COLUMN geneid VARCHAR(25) NOT NULL',
                     'ALTER TABLE ' . TABLE_SCR2GENE . ' MODIFY COLUMN geneid VARCHAR(25) NOT NULL',
                     'ALTER TABLE ' . TABLE_SHARED_COLS . ' MODIFY COLUMN geneid VARCHAR(25)',
                     'DROP TABLE ' . TABLEPREFIX . '_hits',
                 ),
                 '3.0-07' =>
                 array(
                     'UPDATE ' . TABLE_COLS . ' SET description_legend_short = REPLACE(description_legend_short, "/76 chomosomes", "/760 chromosomes"), description_legend_full = REPLACE(description_legend_full, "/76 chomosomes", "/760 chromosomes"), form_type = REPLACE(form_type, "/76 chomosomes", "/760 chromosomes") WHERE id = "VariantOnGenome/Frequency"',
                 ),
                 '3.0-07b' =>
                 array(
                     'UPDATE ' . TABLE_COLS . ' SET standard = 0 WHERE id = "VariantOnGenome/Restriction_site"',
                     'ALTER TABLE ' . TABLE_VARIANTS . ' ADD COLUMN average_frequency FLOAT UNSIGNED AFTER mapping_flags',
                 ),
                 '3.0-07c' =>
                 array(
                     'ALTER TABLE ' . TABLE_VARIANTS . ' ADD INDEX (average_frequency)',
                 ),
                 '3.0-10b' =>
                 array(
                     'UPDATE ' . TABLE_COLS . ' SET preg_pattern = "/^(chr(\\\\d{1,2}|[XYM])|(C(\\\\d{1,2}|[XYM])orf[\\\\d][\\\\dA-Z]*-|[A-Z][A-Z0-9]+-)?(C(\\\\d{1,2}|[XYM])orf[\\\\d][\\\\dA-Z]*|[A-Z][A-Z0-9-]+))_\\\\d{6}$/" WHERE id = "VariantOnGenome/DBID" AND preg_pattern = "/^(chr(\\\\d{1,2}|[XYM])|(C(\\\\d{1,2}|[XYM])orf[\\\\d][\\\\dA-Z]*-|[A-Z][A-Z0-9]+-)?(C(\\\\d{1,2}|[XYM])orf[\\\\d][\\\\dA-Z]*|[A-Z][A-Z0-9]+))_\\\\d{6}$/"',
                 ),
                 '3.0-10c' =>
                 array(
                     'UPDATE ' . TABLE_LOGS . ' SET name = "Event" WHERE name = "Error" AND event = "ColEdit" AND log LIKE "Column % reset to new defaults%"',
                     'INSERT IGNORE INTO ' . TABLE_EFFECT . ' VALUES ("00", "./."), ("01", "./-"), ("03", "./-?"), ("05", "./?"), ("07", "./+?"), ("09", "./+"), ("10", "-/."), ("30", "-?/."), ("50", "?/."), ("70", "+?/."), ("90", "+/.")',
                     'ALTER TABLE ' . TABLE_EFFECT . ' MODIFY COLUMN id TINYINT(2) UNSIGNED ZEROFILL NOT NULL',
                     'ALTER TABLE ' . TABLE_VARIANTS . ' MODIFY COLUMN effectid TINYINT(2) UNSIGNED ZEROFILL',
                     'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' MODIFY COLUMN effectid TINYINT(2) UNSIGNED ZEROFILL',
                 ),
                 '3.0-10d' =>
                 array(
                     'ALTER TABLE ' . TABLE_CONFIG . ' MODIFY COLUMN mutalyzer_soap_url VARCHAR(100) NOT NULL DEFAULT "https://mutalyzer.nl/services"',
                     'UPDATE ' . TABLE_CONFIG . ' SET mutalyzer_soap_url = "https://mutalyzer.nl/services" WHERE mutalyzer_soap_url = "http://www.mutalyzer.nl/2.0/services"',
                 ),
                 '3.0-11b' =>
                 array(
                     'INSERT INTO ' . TABLE_SOURCES . ' VALUES ("pubmed_gene", "http://www.ncbi.nlm.nih.gov/pubmed?LinkName=gene_pubmed&from_uid={{ ID }}")',
                 ),
                 '3.0-11c' =>
                 array(
                     'UPDATE ' . TABLE_COUNTRIES . ' SET name = "Libya" WHERE id = "LY" AND name = "Libyan Arab Jamahiriya"',
                     'UPDATE ' . TABLE_COUNTRIES . ' SET name = "Saint Helena, Ascension and Tristan da Cunha" WHERE id = "SH" AND name = "Saint Helena"',
                     'INSERT INTO ' . TABLE_COUNTRIES . ' VALUES ("SS", "South Sudan")',
                 ),
                 '3.0-11d' =>
                     array(
                         'UPDATE ' . TABLE_CONFIG . ' SET mutalyzer_soap_url = "https://mutalyzer.nl/services" WHERE mutalyzer_soap_url = "http://www.mutalyzer.nl/2.0/services"',
                     ),
                 '3.0-13' =>
                     array(
                         'UPDATE ' . TABLE_COLS . ' SET select_options = "intergenic\r\nnear-gene-5\r\nutr-5\r\ncoding\r\ncoding-near-splice\r\ncoding-synonymous\r\ncoding-synonymous-near-splice\r\ncodingComplex\r\ncodingComplex-near-splice\r\nframeshift\r\nframeshift-near-splice\r\nmissense\r\nmissense-near-splice\r\nsplice-5\r\nintron\r\nsplice-3\r\nstop-gained\r\nstop-gained-near-splice\r\nstop-lost\r\nstop-lost-near-splice\r\nutr-3\r\nnear-gene-3" WHERE select_options = "intergenic\r\nnear-gene-5\r\nutr-5\r\ncoding\r\ncoding-near-splice\r\ncodingComplex\r\ncodingComplex-near-splice\r\nframeshift\r\nframeshift-near-splice\r\nsplice-5\r\nintron\r\nsplice-3\r\nutr-3\r\nnear-gene-3" AND id = "VariantOnTranscript/GVS/Function"',
                         'UPDATE ' . TABLE_SHARED_COLS . ' SET select_options = "intergenic\r\nnear-gene-5\r\nutr-5\r\ncoding\r\ncoding-near-splice\r\ncoding-synonymous\r\ncoding-synonymous-near-splice\r\ncodingComplex\r\ncodingComplex-near-splice\r\nframeshift\r\nframeshift-near-splice\r\nmissense\r\nmissense-near-splice\r\nsplice-5\r\nintron\r\nsplice-3\r\nstop-gained\r\nstop-gained-near-splice\r\nstop-lost\r\nstop-lost-near-splice\r\nutr-3\r\nnear-gene-3" WHERE select_options = "intergenic\r\nnear-gene-5\r\nutr-5\r\ncoding\r\ncoding-near-splice\r\ncodingComplex\r\ncodingComplex-near-splice\r\nframeshift\r\nframeshift-near-splice\r\nsplice-5\r\nintron\r\nsplice-3\r\nutr-3\r\nnear-gene-3" AND colid = "VariantOnTranscript/GVS/Function"',
                     ),
                 '3.0-14' =>
                     array(
                         'ALTER TABLE ' . TABLE_DISEASES . ' MODIFY COLUMN symbol VARCHAR(25) NOT NULL',
                     ),
                 '3.0-14b' =>
                     array(
                         // In order to resize the transcript's ID column, we need to drop the foreign key contraint, otherwise it won't allow us to modify the column.
                         'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' DROP FOREIGN KEY ' . TABLE_VARIANTS_ON_TRANSCRIPTS . '_fk_transcriptid',
                         'ALTER TABLE ' . TABLE_TRANSCRIPTS . ' MODIFY COLUMN id MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT',
                         'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' MODIFY COLUMN transcriptid MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL',
                         'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' ADD CONSTRAINT ' . TABLE_VARIANTS_ON_TRANSCRIPTS . '_fk_transcriptid FOREIGN KEY (transcriptid) REFERENCES ' . TABLE_TRANSCRIPTS . ' (id) ON DELETE CASCADE ON UPDATE CASCADE',
                     ),
                 '3.0-14c' =>
                     array(
                         'ALTER TABLE ' . TABLE_TRANSCRIPTS . ' MODIFY COLUMN id MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT',
                     ),
                 '3.0-14d' =>
                     array(
                         'UPDATE ' . TABLE_DISEASES . ' SET symbol = "Healthy/Control" WHERE id_omim IS NULL AND created_by = 0 AND symbol = "Healty/Control"',
                     ),
                 '3.0-15a' =>
                     array('CREATE TABLE IF NOT EXISTS ' . TABLE_COLLEAGUES . '(
                            userid_from SMALLINT(5) UNSIGNED ZEROFILL NOT NULL,
                            userid_to   SMALLINT(5) UNSIGNED ZEROFILL NOT NULL,
                            allow_edit  BOOLEAN NOT NULL DEFAULT 0,
                            PRIMARY KEY (userid_from, userid_to),
                            INDEX (userid_to),
                            CONSTRAINT ' . TABLE_COLLEAGUES .  '_fk_userid_from FOREIGN KEY (userid_from) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE CASCADE ON UPDATE CASCADE,
                            CONSTRAINT ' . TABLE_COLLEAGUES . '_fk_userid_to FOREIGN KEY (userid_to) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE CASCADE ON UPDATE CASCADE)
                            ENGINE=InnoDB, DEFAULT CHARACTER SET utf8',
                     ),
                 '3.0-16a' =>
                    array('ALTER TABLE ' . TABLE_TRANSCRIPTS . ' ADD COLUMN remarks TEXT NOT NULL AFTER id_protein_uniprot'),
                 '3.0-16b' =>
                    array('ALTER TABLE ' . TABLE_DISEASES .
                               ' ADD COLUMN tissues  TEXT NOT NULL AFTER id_omim, 
                                 ADD COLUMN features TEXT NOT NULL AFTER tissues,
                                 ADD COLUMN remarks TEXT NOT NULL AFTER features',
                        ),
                 '3.0-16c' =>
                    array(
                        'ALTER TABLE ' . TABLE_CONFIG . ' ADD COLUMN allow_submitter_registration BOOLEAN NOT NULL DEFAULT ' . (int) (!LOVD_plus) . ' AFTER include_in_listing',
                        'CREATE TABLE ' . TABLE_ANNOUNCEMENTS . ' (
                            id SMALLINT(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
                            type VARCHAR(15) NOT NULL DEFAULT "information",
                            announcement TEXT NOT NULL,
                            start_date DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00",
                            end_date DATETIME NOT NULL DEFAULT "9999-12-31 23:59:59",
                            lovd_read_only BOOLEAN NOT NULL DEFAULT 0,
                            created_by SMALLINT(5) UNSIGNED ZEROFILL,
                            created_date DATETIME NOT NULL,
                            edited_by SMALLINT(5) UNSIGNED ZEROFILL,
                            edited_date DATETIME,
                            PRIMARY KEY (id),
                            INDEX (created_by),
                            INDEX (edited_by),
                            CONSTRAINT ' . TABLE_ANNOUNCEMENTS . '_fk_created_by FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE,
                            CONSTRAINT ' . TABLE_ANNOUNCEMENTS . '_fk_edited_by FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE)
                            ENGINE=InnoDB,
                            DEFAULT CHARACTER SET utf8',
                    ),
                 '3.0-17b' =>
                     array(
                         'ALTER TABLE ' . TABLE_USERS . ' ADD COLUMN auth_token CHAR(32) AFTER password_force_change, ADD COLUMN auth_token_expires DATETIME AFTER auth_token',
                     ),
                 '3.0-17c' =>
                     array(
                         'UPDATE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' SET position_c_start_intron = 0 WHERE position_c_start IS NOT NULL AND position_c_start_intron IS NULL',
                         'UPDATE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' SET position_c_end_intron = 0 WHERE position_c_end IS NOT NULL AND position_c_end_intron IS NULL',
                     ),
                 '3.0-18' =>
                     array(
                         // These two will be ignored by LOVD+.
                         'INSERT IGNORE INTO ' . TABLE_SOURCES . ' VALUES ("pubmed_article", "http://www.ncbi.nlm.nih.gov/pubmed/{{ ID }}")',
                         'INSERT IGNORE INTO ' . TABLE_LINKS . ' VALUES (NULL, "Alamut", "{Alamut:[1]:[2]}", "<A href=\"http://127.0.0.1:10000/show?request=[1]:[2]\" target=\"_blank\">Alamut</A>", "Links directly to the variant in the Alamut software.\r\n[1] = The chromosome letter or number.\r\n[2] = The genetic change on genome level.\r\n\r\nExample:\r\n{Alamut:16:21854780G>A}", 0, NOW(), NULL, NULL)',
                         'UPDATE ' . TABLE_COLS . ' SET mandatory = 0 WHERE id = "VariantOnTranscript/Exon"',
                     ),
                 '3.0-18a' =>
                     array(
                         'UPDATE ' . TABLE_COLS . ' SET preg_pattern = "/^(chr(\\\\d{1,2}|[XYM])|(C(\\\\d{1,2}|[XYM])orf[\\\\d][\\\\dA-Z]*-|[A-Z][A-Z0-9]*-)?(C(\\\\d{1,2}|[XYM])orf[\\\\d][\\\\dA-Z]*|[A-Z][A-Z0-9-]*))_\\\\d{6}$/" WHERE id = "VariantOnGenome/DBID";',
                     ),
                 '3.0-18b' =>
                     array(
                         'UPDATE ' . TABLE_SOURCES . ' SET url = "https://www.ncbi.nlm.nih.gov/gene?cmd=Retrieve&dopt=full_report&list_uids={{ ID }}" WHERE id = "entrez"',
                         'UPDATE ' . TABLE_SOURCES . ' SET url = "https://www.ncbi.nlm.nih.gov/nuccore/{{ ID }}" WHERE id = "genbank"',
                         'UPDATE ' . TABLE_SOURCES . ' SET url = "https://www.ncbi.nlm.nih.gov/gtr/genes/{{ ID }}" WHERE id = "genetests"',
                         'UPDATE ' . TABLE_SOURCES . ' SET url = "https://www.ncbi.nlm.nih.gov/pubmed?LinkName=gene_pubmed&from_uid={{ ID }}" WHERE id = "pubmed_gene"',
                         'UPDATE ' . TABLE_SOURCES . ' SET url = "https://www.ncbi.nlm.nih.gov/pubmed/{{ ID }}" WHERE id = "pubmed_article"',
                     ),
                 '3.0-18c' =>
                     array(
                         'UPDATE ' . TABLE_LINKS . ' SET replace_text = "<A href=\"https://www.ncbi.nlm.nih.gov/pubmed/[2]\" target=\"_blank\">[1]</A>" WHERE name = "PubMed" AND replace_text = "<A href=\"http://www.ncbi.nlm.nih.gov/pubmed/[2]\" target=\"_blank\">[1]</A>"',
                         'UPDATE ' . TABLE_LINKS . ' SET replace_text = "<A href=\"https://www.ncbi.nlm.nih.gov/SNP/snp_ref.cgi?rs=[1]\" target=\"_blank\">dbSNP</A>" WHERE name = "DbSNP" AND replace_text = "<A href=\"http://www.ncbi.nlm.nih.gov/SNP/snp_ref.cgi?rs=[1]\" target=\"_blank\">dbSNP</A>"',
                         'UPDATE ' . TABLE_LINKS . ' SET replace_text = "<A href=\"https://www.ncbi.nlm.nih.gov/nuccore/[1]\" target=\"_blank\">GenBank</A>" WHERE name = "GenBank" AND replace_text = "<A href=\"http://www.ncbi.nlm.nih.gov/entrez/viewer.fcgi?cmd=Retrieve&amp;db=nucleotide&amp;dopt=GenBank&amp;list_uids=[1]\" target=\"_blank\">GenBank</A>"',
                     ),
                 '3.0-18d' =>
                    array(
                        'ALTER TABLE ' . TABLE_USERS . '
                            ALTER department SET DEFAULT "",
                            ALTER telephone SET DEFAULT "",
                            ALTER reference SET DEFAULT "",
                            ALTER password_force_change SET DEFAULT 0,
                            ALTER level SET DEFAULT 1,
                            ALTER allowed_ip SET DEFAULT "*",
                            ALTER login_attempts SET DEFAULT 0',
                        'ALTER TABLE ' . TABLE_CHROMOSOMES . ' ALTER sort_id SET DEFAULT 0',
                        'ALTER TABLE ' . TABLE_GENES . '
                            MODIFY chrom_band VARCHAR(40) NOT NULL DEFAULT "",
                            ALTER refseq_genomic SET DEFAULT "",
                            ALTER refseq_UD SET DEFAULT "",
                            ALTER reference SET DEFAULT "",
                            ALTER url_homepage SET DEFAULT "",
                            MODIFY url_external TEXT,
                            ALTER allow_download SET DEFAULT 0,
                            ALTER allow_index_wiki SET DEFAULT 0,
                            ALTER show_hgmd SET DEFAULT 0,
                            ALTER show_genecards SET DEFAULT 0,
                            ALTER show_genetests SET DEFAULT 0,
                            MODIFY note_index TEXT,
                            MODIFY note_listing TEXT,
                            ALTER refseq SET DEFAULT "",
                            ALTER refseq_url SET DEFAULT "",
                            ALTER disclaimer SET DEFAULT 1,
                            MODIFY disclaimer_text TEXT,
                            MODIFY header TEXT,
                            ALTER header_align SET DEFAULT -1,
                            MODIFY footer TEXT,
                            ALTER footer_align SET DEFAULT -1',
                        'ALTER TABLE ' . TABLE_CURATES . ' ALTER allow_edit SET DEFAULT 0',
                        'ALTER TABLE ' . TABLE_TRANSCRIPTS . '
                            ALTER id_ensembl SET DEFAULT "",
                            ALTER id_protein_ncbi SET DEFAULT "",
                            ALTER id_protein_ensembl SET DEFAULT "",
                            ALTER id_protein_uniprot SET DEFAULT "",
                            MODIFY remarks TEXT',
                        'ALTER TABLE ' . TABLE_DISEASES . '
                            ALTER symbol SET DEFAULT "-",
                            MODIFY tissues TEXT,
                            MODIFY features TEXT,
                            MODIFY remarks TEXT',
                        'ALTER TABLE ' . TABLE_ALLELES . ' ALTER display_order SET DEFAULT 0',
                        'ALTER TABLE ' . TABLE_VARIANTS . ' ALTER allele SET DEFAULT 0',
                        'ALTER TABLE ' . TABLE_COLS . '
                            ALTER col_order SET DEFAULT 0,
                            ALTER width SET DEFAULT 50,
                            ALTER hgvs SET DEFAULT 0,
                            ALTER standard SET DEFAULT 0,
                            ALTER mandatory SET DEFAULT 0,
                            MODIFY description_form TEXT,
                            MODIFY description_legend_short TEXT,
                            MODIFY description_legend_full TEXT,
                            ALTER mysql_type SET DEFAULT "VARCHAR(50)",
                            MODIFY form_type TEXT,
                            MODIFY select_options TEXT,
                            ALTER preg_pattern SET DEFAULT "",
                            ALTER public_view SET DEFAULT 1,
                            ALTER public_add SET DEFAULT 1,
                            ALTER allow_count_all SET DEFAULT 0',
                        'ALTER TABLE ' . TABLE_SHARED_COLS . '
                            ALTER col_order SET DEFAULT 0,
                            ALTER width SET DEFAULT 50,
                            ALTER mandatory SET DEFAULT 0,
                            MODIFY description_form TEXT,
                            MODIFY description_legend_short TEXT,
                            MODIFY description_legend_full TEXT,
                            MODIFY select_options TEXT,
                            ALTER public_view SET DEFAULT 1,
                            ALTER public_add SET DEFAULT 1',
                        'ALTER TABLE ' . TABLE_LINKS . ' MODIFY description TEXT',
                        'ALTER TABLE ' . TABLE_CONFIG . ' ALTER system_title SET DEFAULT "LOVD - Leiden Open Variation Database",
                            ALTER institute SET DEFAULT "",
                            ALTER location_url SET DEFAULT "",
                            ALTER email_address SET DEFAULT "",
                            ALTER send_admin_submissions SET DEFAULT 0,
                            ALTER api_feed_history SET DEFAULT 0,
                            ALTER refseq_build SET DEFAULT "hg38",
                            ALTER proxy_host SET DEFAULT "",
                            ALTER proxy_username SET DEFAULT "",
                            ALTER proxy_password SET DEFAULT "",
                            ALTER omim_apikey SET DEFAULT "",
                            ALTER send_stats SET DEFAULT 1,
                            ALTER include_in_listing SET DEFAULT 1,
                            ALTER lock_users SET DEFAULT 1,
                            ALTER allow_unlock_accounts SET DEFAULT 1,
                            ALTER allow_submitter_mods SET DEFAULT 1,
                            ALTER allow_count_hidden_entries SET DEFAULT 0,
                            ALTER use_ssl SET DEFAULT 0,
                            ALTER use_versioning SET DEFAULT 0,
                            ALTER lock_uninstall SET DEFAULT 1',
                        'ALTER TABLE ' . TABLE_STATUS . ' ALTER lock_update SET DEFAULT 0',
                        'ALTER TABLE ' . TABLE_MODULES . '
                            ALTER version SET DEFAULT "",
                            ALTER description SET DEFAULT "",
                            ALTER active SET DEFAULT 0,
                            MODIFY settings TEXT',
                    ),
                 '3.0-19a' => array('INSERT INTO ' . TABLE_SOURCES . ' VALUES ("hpo_disease", "http://compbio.charite.de/hpoweb/showterm?disease=OMIM:{{ ID }}")'),
                 '3.0-19b' => array('INSERT INTO ' . TABLE_EFFECT . ' VALUES ("06", "./#"), 
                     ("08", "./+*"), ("16", "-/#"), ("18", "-/+*"), ("36", "-?/#"), 
                     ("38", "-?/+*"), ("56", "?/#"), ("58", "?/+*"), ("60", "#/."), ("61", "#/-"),
                     ("63", "#/-?"), ("65", "#/?"), ("66", "#/#"), ("67", "#/+?"), ("68", "#/+*"),
                     ("69", "#/+"), ("76", "+?/#"), ("78", "+?/+*"), ("80", "+*/."), 
                     ("81", "+*/-"), ("83", "+*/-?"), ("85", "+*/?"), ("86", "+*/#"), 
                     ("87", "+*/+?"), ("88", "+*/+*"), ("89", "+*/+"), ("96", "+/#"), 
                     ("98", "+/+*");'),
                 '3.0-20b' => array(
                     'CREATE TABLE IF NOT EXISTS ' . TABLE_SCHEDULED_IMPORTS . ' (
                          filename VARCHAR(255) NOT NULL,
                          priority TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                          in_progress BOOLEAN NOT NULL DEFAULT 0,
                          scheduled_by SMALLINT(5) UNSIGNED ZEROFILL,
                          scheduled_date DATETIME NOT NULL,
                          process_errors TEXT,
                          processed_by SMALLINT(5) UNSIGNED ZEROFILL,
                          processed_date DATETIME,
                          PRIMARY KEY (filename),
                          INDEX (scheduled_by),
                          INDEX (processed_by),
                          CONSTRAINT ' . TABLE_SCHEDULED_IMPORTS . '_fk_scheduled_by FOREIGN KEY (scheduled_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE,
                          CONSTRAINT ' . TABLE_SCHEDULED_IMPORTS . '_fk_processed_by FOREIGN KEY (processed_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL ON UPDATE CASCADE)
                      ENGINE=InnoDB,
                      DEFAULT CHARACTER SET utf8',
                 ),
                 '3.0-20c' => array(
                     'ALTER TABLE ' . TABLE_CHROMOSOMES . ' ADD COLUMN hg38_id_ncbi VARCHAR(20) NOT NULL AFTER hg19_id_ncbi',
                     // Weird, but much simpler... so, oh well. All chromosomes got updated one version, except M.
                     'UPDATE ' . TABLE_CHROMOSOMES . ' SET hg38_id_ncbi = CONCAT(LEFT(hg19_id_ncbi, 10), (TRIM(LEADING "." FROM RIGHT(hg19_id_ncbi, 2))+1)) WHERE name != "M"',
                     'UPDATE ' . TABLE_CHROMOSOMES . ' SET hg38_id_ncbi = hg19_id_ncbi WHERE name = "M"',
                 ),
                 '3.0-21b' => array(
                     'UPDATE ' . TABLE_SOURCES . ' SET url = "https://www.genenames.org/data/gene-symbol-report/#!/hgnc_id/HGNC:{{ ID }}" WHERE id = "hgnc"',
                 ),
                 '3.0-21c' => array(
                     'SET @bExists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "' . TABLE_USERS . '" AND COLUMN_NAME = "username" AND CHARACTER_MAXIMUM_LENGTH < 50)',
                     'SET @sSQL := IF(@bExists < 1, \'SELECT "INFO: Column not found or already enlarged."\', "
                            ALTER TABLE ' . TABLE_USERS . ' MODIFY COLUMN username VARCHAR(50) NOT NULL")',
                     'PREPARE Statement FROM @sSQL',
                     'EXECUTE Statement',
                 ),
             );

    if ($sCalcVersionDB < lovd_calculateVersion('3.0-alpha-01')) {
        // Simply reload all custom columns.
        require ROOT_PATH . 'install/inc-sql-columns.php';
        $aUpdates['3.0-alpha-01'][] = 'DELETE FROM ' . TABLE_COLS . ' WHERE col_order < 255';
        $aUpdates['3.0-alpha-01'] = array_merge($aUpdates['3.0-alpha-01'], $aColSQL);
    }

    if ($sCalcVersionDB < lovd_calculateVersion('3.0-alpha-07')) {
        // DROP VariantOnTranscript/DBID if it exists.
        $aColumns = $_DB->query('DESCRIBE ' . TABLE_VARIANTS_ON_TRANSCRIPTS)->fetchAllColumn();
        if (in_array('VariantOnTranscript/DBID', $aColumns)) {
            $aUpdates['3.0-alpha-07'][] = 'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' DROP COLUMN `VariantOnTranscript/DBID`';
        }
    }

    if ($sCalcVersionDB < lovd_calculateVersion('3.0-alpha-07b')) {
        // DROP Individual/Times_Reported if it exists and copy its data to panel_size.
        $aColumns = $_DB->query('DESCRIBE ' . TABLE_INDIVIDUALS)->fetchAllColumn();
        if (in_array('Individual/Times_Reported', $aColumns)) {
            $aUpdates['3.0-alpha-07b'][] = 'UPDATE ' . TABLE_INDIVIDUALS . ' SET panel_size = `Individual/Times_Reported`';
            $aUpdates['3.0-alpha-07b'][] = 'ALTER TABLE ' . TABLE_INDIVIDUALS . ' DROP COLUMN `Individual/Times_Reported`';
        }
        $aUpdates['3.0-alpha-07b'][] = 'DELETE FROM ' . TABLE_COLS . ' WHERE id = "Individual/Times_Reported"';
    }

    if ($sCalcVersionDB < lovd_calculateVersion('3.0-alpha-07c')) {
        // SET all standard custom columns and custom links to created_by new LOVD user.
        $aColumns = array(
                            'Individual/Lab_ID', 'Individual/Reference', 'Individual/Remarks', 'Individual/Remarks_Non_Public', 'Individual/Gender', 'Individual/Mutation/Origin',
                            'Individual/Origin/Geographic', 'Individual/Origin/Ethnic', 'Individual/Origin/Population', 'Screening/Date', 'Screening/Technique', 'Screening/Template',
                            'Screening/Tissue', 'VariantOnGenome/DBID', 'VariantOnGenome/DNA', 'VariantOnGenome/DNA_published', 'VariantOnGenome/Frequency', 'VariantOnGenome/Reference',
                            'VariantOnGenome/Remarks', 'VariantOnGenome/Restriction_site', 'VariantOnGenome/Type', 'VariantOnTranscript/DNA', 'VariantOnTranscript/DNA_published',
                            'VariantOnTranscript/Exon', 'VariantOnTranscript/Location', 'VariantOnTranscript/Protein', 'VariantOnTranscript/RNA'
                         );
        $aUpdates['3.0-alpha-07c'][] = 'UPDATE ' . TABLE_COLS . ' SET created_by = 0 WHERE id IN ("'. implode('", "', $aColumns) . '")';
        $aUpdates['3.0-alpha-07c'][] = 'UPDATE ' . TABLE_LINKS . ' SET created_by = 0 WHERE id <= 4';
    }

    if ($sCalcVersionDB < lovd_calculateVersion('3.0-alpha-07d')) {
        // INSERT chromosomes in the new TABLE_CHROMOSOMES.
        require ROOT_PATH . 'install/inc-sql-chromosomes.php';
        $aUpdates['3.0-alpha-07d']['chr_values'] = $aChromosomeSQL[0];
    }

    if ($sCalcVersionDB < lovd_calculateVersion('3.0-beta-03b')) {
        // CHANGE DNA_published to Published_as in TABLE_VARIANTS & TABLE_VARIANTS_ON_TRANSCRIPTS if exists.
        $aColumns = $_DB->query('DESCRIBE ' . TABLE_VARIANTS)->fetchAllColumn();
        if (in_array('VariantOnGenome/DNA_published', $aColumns)) {
            $aUpdates['3.0-beta-03b'][] = 'ALTER TABLE ' . TABLE_VARIANTS . ' CHANGE `VariantOnGenome/DNA_Published` `VariantOnGenome/Published_as` VARCHAR(100)';
        }
        $aColumns = $_DB->query('DESCRIBE ' . TABLE_VARIANTS_ON_TRANSCRIPTS)->fetchAllColumn();
        if (in_array('VariantOnTranscript/DNA_published', $aColumns)) {
            $aUpdates['3.0-beta-03b'][] = 'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' CHANGE `VariantOnTranscript/DNA_Published` `VariantOnTranscript/Published_as` VARCHAR(100)';
        }
    }

    if ($sCalcVersionDB < lovd_calculateVersion('3.0-beta-03d')) {
        // INSERT allele values in the new TABLE_ALLELES.
        require ROOT_PATH . 'install/inc-sql-alleles.php';
        $aUpdates['3.0-beta-03d']['allele_values'] = $aAlleleSQL[0];
    }

    if ($sCalcVersionDB < lovd_calculateVersion('3.0-beta-05')) {
        // Make Phenotype/Inheritance long enough to actually fit the values in its selection list.
        $aColumns = $_DB->query('DESCRIBE ' . TABLE_PHENOTYPES)->fetchAllColumn();
        if (in_array('Phenotype/Inheritance', $aColumns)) {
            $aUpdates['3.0-beta-05'][] = 'ALTER TABLE ' . TABLE_PHENOTYPES . ' MODIFY `Phenotype/Inheritance` VARCHAR(50)';
        }
    }





    // To make sure we upgrade the database correctly, we add the current version to the list...
    if (!isset($aUpdates[$_SETT['system']['version']])) {
        $aUpdates[$_SETT['system']['version']] = array();
    }

    require ROOT_PATH . 'class/progress_bar.php';
    // FIXME; if we're not in post right now, don't send the form in POST either! (GET variables then should be put in input fields then)
    $sFormNextPage = '<FORM action="' . $_SERVER['REQUEST_URI'] . '" method="post" id="upgrade_form">' . "\n";
    foreach ($_POST as $key => $val) {
        // Added htmlspecialchars to prevent XSS and allow values to include quotes.
        if (is_array($val)) {
            foreach ($val as $value) {
                $sFormNextPage .= '          <INPUT type="hidden" name="' . $key . '[]" value="' . htmlspecialchars($value) . '">' . "\n";
            }
        } else {
            $sFormNextPage .= '          <INPUT type="hidden" name="' . $key . '" value="' . htmlspecialchars($val) . '">' . "\n";
        }
    }
    $sFormNextPage .= '          <INPUT type="submit" id="submit" value="Proceed &raquo;">' . "\n" .
                      '        </FORM>';
    // This already puts the progress bar on the screen.
    $_BAR = new ProgressBar('', 'Checking upgrade lock...', $sFormNextPage);

    $_T->printFooter(false); // The false prevents the footer to actually close the <BODY> and <HTML> tags.



    // Now we're still in the <BODY> so the progress bar can add <SCRIPT> tags as much as it wants.
    flush();



    // Try to update the upgrade lock.
    $sQ = 'UPDATE ' . TABLE_STATUS . ' SET lock_update = 1 WHERE lock_update = 0';
    $nMax = 30;

    for ($i = 0; $i < $nMax; $i ++) {
        $bLocked = !$_DB->exec($sQ);
        if (!$bLocked) {
            break;
        }

        // No update means that someone else is updating the system.
        $_BAR->setMessage('Update lock is in place, so someone else is already upgrading the database.<BR>Waiting for other user to finish... (' . ($nMax - $i) . ')');
        flush();
        sleep(1);
    }

    if ($bLocked) {
        // Other user is taking ages! Or somethings wrong...
        $_BAR->setMessage('Other user upgrading the database is still not finished.<BR>' . (isset($_GET['force_lock'])? 'Forcing upgrade as requested...' : 'This may indicate something went wrong during upgrade.'));
        if (isset($_GET['force_lock'])) {
            $bLocked = false;
        }
    } else {
        $_BAR->setMessage('Upgrading database backend...');
    }
    flush();





    if (!$bLocked) {
        // There we go...

        // This recursive count returns a higher count then we would seem to want at first glance,
        // because each version's array of queries count as one as well.
        // However, because we will run one additional query per version, this number will be correct anyway.
        // 2012-02-02; 3.0-beta-02; But of course we should exclude the older versions...
        foreach ($aUpdates as $sVersion => $aSQL) {
            if (lovd_calculateVersion($sVersion) <= $sCalcVersionDB || lovd_calculateVersion($sVersion) > $sCalcVersionFiles) {
                unset($aUpdates[$sVersion]);
            }
        }
        $nSQL = count($aUpdates, true);

        // Actually run the SQL...
        $nSQLDone = 0;
        $nSQLDonePercentage = 0;
        $nSQLDonePercentagePrev = 0;
        $nSQLFailed = 0;
        $sSQLFailed = '';

        foreach ($aUpdates as $sVersion => $aSQL) {
            $_BAR->setMessage('To ' . $sVersion . '...');

            // Also set update_checked_date to NULL, so LOVD will again check for updates as soon as possible.
            $aSQL[] = 'UPDATE ' . TABLE_STATUS . ' SET version = "' . $sVersion . '", updated_date = NOW(), update_level = 0, update_checked_date = NULL';

            // Loop needed queries...
            foreach ($aSQL as $i => $sSQL) {
                $i ++;
                if (!$nSQLFailed) {
                    $q = $_DB->query($sSQL, false, false); // This means that there is no SQL injection check here. But hey - these are our own queries.
                    if (!$q) {
                        $nSQLFailed ++;
                        // Error when running query.
                        $sError = $_DB->formatError();
                        lovd_queryError('RunUpgradeSQL', $sSQL, $sError, false);
                        $sSQLFailed = 'Error!<BR><BR>\n\n' .
                                      'Error while executing query ' . $i . ':\n' .
                                      '<PRE style="background : #F0F0F0;">' . htmlspecialchars($sError) . '</PRE><BR>\n\n' .
                                      'This implies these MySQL queries need to be executed manually:<BR>\n' .
                                      '<PRE style="background : #F0F0F0;">\n<SPAN style="background : #C0C0C0;">' . sprintf('%' . strlen(count($aSQL)) . 'd', $i) . '</SPAN> ' . htmlspecialchars($sSQL) . ';\n';

                    } else {
                        $nSQLDone ++;

                        $nSQLDonePercentage = floor(100*$nSQLDone / $nSQL); // Don't want to show 100% when an error occurs at 99.5%.
                        if ($nSQLDonePercentage != $nSQLDonePercentagePrev) {
                            $_BAR->setProgress($nSQLDonePercentage);
                            $nSQLDonePercentagePrev = $nSQLDonePercentage;
                        }

                        flush();
                        usleep(1000);
                    }

                } else {
                    // Something went wrong, so we need to print out the remaining queries...
                    $nSQLFailed ++;
                    $sSQLFailed .= '<SPAN style="background : #C0C0C0;">' . sprintf('%' . strlen(count($aSQL)) . 'd', $i) . '</SPAN> ' . htmlspecialchars($sSQL) . ';\n';
                }
            }

            if ($nSQLFailed) {
                $sSQLFailed .= '</PRE>';
                $_BAR->setMessage($sSQLFailed);
                $_BAR->setMessage('After executing th' . ($nSQLFailed == 1? 'is query' : 'ese queries') . ', please try again.', 'done');
                $_BAR->setMessageVisibility('done', true);
                break;
            }
            usleep(300000);
        }

        if (!$nSQLFailed) {
            // Upgrade complete, all OK!
            lovd_writeLog('Install', 'Upgrade', 'Successfully upgraded LOVD from ' . $_STAT['version'] . ' to ' . $_SETT['system']['version'] . ', executing ' . $nSQLDone . ' quer' . ($nSQLDone == 1? 'y' : 'ies'));
            $_BAR->setProgress(100);
            $_BAR->setMessage('Successfully upgraded to ' . $_SETT['system']['version'] . '!<BR>Executed ' . $nSQLDone . ' database quer' . ($nSQLDone == 1? 'y' : 'ies') . '.');
        } else {
            // Bye bye, they should not see the form!
            print('</BODY>' . "\n" .
                  '</HTML>' . "\n");
            exit;
        }

        // Remove update lock.
        $_DB->query('UPDATE ' . TABLE_STATUS . ' SET lock_update = 0');
    }

    // Now that this is over, let the user proceed to whereever they were going!
    if ($bLocked) {
        // Have to force upgrade...
        $_SERVER['REQUEST_URI'] .= ($_SERVER['QUERY_STRING']? '&' : '?') . 'force_lock';
    } else {
        // Remove the force_lock thing again... (might not be there, but who cares!)
        $_SERVER['REQUEST_URI'] = preg_replace('/[?&]force_lock$/', '', $_SERVER['REQUEST_URI']);
    }

    print('<SCRIPT type="text/javascript">document.forms[\'upgrade_form\'].action=\'' . str_replace('\'', '\\\'', $_SERVER['REQUEST_URI']) . '\';</SCRIPT>' . "\n");
    if ($bLocked) {
        print('<SCRIPT type="text/javascript">document.forms[\'upgrade_form\'].submit.value = document.forms[\'upgrade_form\'].submit.value.replace(\'Proceed\', \'Force upgrade\');</SCRIPT>' . "\n");
    }
    $_BAR->setMessageVisibility('done', true);

    // Resets the mapping timer so that the automatic mapper will begin mapping when the upgrade is finished.
    $_SESSION['mapping']['time_complete'] = 0;
    $_SESSION['mapping']['time_error'] = 0;
    print('</BODY>' . "\n" .
          '</HTML>' . "\n");
    exit;
}
?>
