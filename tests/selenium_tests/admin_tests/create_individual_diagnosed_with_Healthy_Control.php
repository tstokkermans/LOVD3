<?php
require_once 'LOVDSeleniumBaseTestCase.php';

class CreateIndividualDiagnosedWithHealthyControlTest extends LOVDSeleniumBaseTestCase
{
    public function testCreateIndividualDiagnosedWithHealthyControl()
    {
        $this->open(ROOT_URL . "/src/submit");
        $this->click("//div/table/tbody/tr/td/table/tbody/tr/td[2]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/individuals[\s\S]create$/', $this->getLocation()));
        $this->type("name=Individual/Lab_ID", "1234HealthyCtrl");
        $this->click("link=PubMed");
        $this->type("name=Individual/Reference", "{PMID:[2011]:[21520333]}");
        $this->type("name=Individual/Remarks", "No Remarks");
        $this->type("name=Individual/Remarks_Non_Public", "Still no remarks");
        $this->addSelection("name=active_diseases[]", "label=Healthy/Control (Healthy individual / control)");
        $this->select("name=owned_by", "label=LOVD3 Admin (#00001)");
        $this->select("name=statusid", "label=Public");
        $this->click("//input[@value='Create individual information entry']");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("Successfully created the individual information entry!", $this->getText("css=table[class=info]"));
        $this->waitForPageToLoad("4000");
    }
}
