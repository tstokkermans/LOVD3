<?php
class Example extends PHPUnit_Extensions_SeleniumTestCase
{
  protected function setUp()
  {
    $this->setBrowser("*chrome");
    $this->setBrowserUrl("https://localhost/");
  }

  public function testMyTestCase()
  {
    $this->click("link=Submit new data");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/submit$/',$this->getLocation()));
    $this->chooseOkOnNextConfirmation();
    $this->click("//div/table/tbody/tr/td/table/tbody/tr[2]/td[2]/b");
    $this->assertTrue((bool)preg_match('/^[\s\S]*Please reconsider to submit individual data as well, as it makes the data you submit much more valuable![\s\S]*$/',$this->getConfirmation()));
    sleep(4);
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/variants[\s\S]create$/',$this->getLocation()));
    $this->click("//div/table/tbody/tr/td/table/tbody/tr/td[2]/b");
    $this->click("//tr[@id='ARSD']/td[2]");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/variants[\s\S]create&reference=Transcript&geneid=ARSD$/',$this->getLocation()));
    $this->uncheck("name=ignore_00002");
    $this->uncheck("name=ignore_00003");
    $this->check("name=ignore_00004");
    $this->check("name=ignore_00005");
    $this->type("name=00002_VariantOnTranscript/Exon", "3");
    $this->type("name=00003_VariantOnTranscript/Exon", "3");
    $this->type("name=00002_VariantOnTranscript/DNA", "c.62T>C");
    $this->click("css=button.mapVariant");
    sleep(3);
    $RnaChange = $this->getEval("window.document.getElementById('variantForm').elements[4].value");
    $this->assertTrue((bool)preg_match('/^r\.\([\s\S]\)$/',$this->getExpression($RnaChange)));
    $ProteinChange = $this->getEval("window.document.getElementById('variantForm').elements[5].value");
    $this->assertEquals("p.(Leu21Pro)", $this->getExpression($ProteinChange));
    $this->select("name=00002_effect_reported", "label=Probably affects function");
    $this->select("name=00002_effect_concluded", "label=Probably does not affect function");
    $RnaChangeTwo = $this->getEval("window.document.getElementById('variantForm').elements[4].value");
    $this->assertTrue((bool)preg_match('/^r\.\([\s\S]\)$/',$this->getExpression($RnaChangeTwo)));
    $ProteinChangeTwo = $this->getEval("window.document.getElementById('variantForm').elements[5].value");
    $this->assertEquals("p.(Leu21Pro)", $this->getExpression($ProteinChangeTwo));
    $this->select("name=00003_effect_reported", "label=Probably affects function");
    $this->select("name=00003_effect_concluded", "label=Probably does not affect function");
    $this->select("name=allele", "label=Maternal (confirmed)");
    $GenomicDnaChange = $this->getEval("window.document.getElementById('variantForm').elements[37].value");
    $this->assertEquals("g.2843789A>G", $this->getExpression($GenomicDnaChange));
    $this->click("link=PubMed");
    $this->type("name=VariantOnGenome/Reference", "{PMID:[2011]:[2150333]}");
    $this->type("name=VariantOnGenome/Frequency", "55/18000");
    $this->select("name=effect_reported", "label=Affects function");
    $this->select("name=effect_concluded", "label=Affects function");
    $this->select("name=owned_by", "label=LOVD3 Admin");
    $this->select("name=statusid", "label=Public");
    $this->click("css=input[type=\"submit\"]");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^Successfully processed your submission and sent an email notification to the relevant curator[\s\S]*$/',$this->getText("css=table[class=info]")));
    $this->waitForPageToLoad("4000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/variants\/0000000281$/',$this->getLocation()));
  }
}
?>