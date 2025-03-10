<?php
require_once 'LOVDSeleniumBaseTestCase.php';

use \Facebook\WebDriver\WebDriverBy;
use \Facebook\WebDriver\WebDriverExpectedCondition;

class AddScreeningToIVAIndividualTest extends LOVDSeleniumWebdriverBaseTestCase
{
    public function testAddScreeningToIndividual()
    {
        $this->driver->get(ROOT_URL . "/src/submit/individual/00000001");
        $element = $this->driver->findElement(WebDriverBy::xpath("//div/table/tbody/tr/td/table/tbody/tr[2]/td[2]/b"));
        $element->click();
        
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/screenings[\s\S]create&target=00000001$/', $this->driver->getCurrentURL()));
        $option = $this->driver->findElement(WebDriverBy::xpath('//select[@name="Screening/Template[]"]/option[text()="RNA (cDNA)"]'));
        $option->click();
        $option = $this->driver->findElement(WebDriverBy::xpath('//select[@name="Screening/Template[]"]/option[text()="Protein"]'));
        $option->click();
        $option = $this->driver->findElement(WebDriverBy::xpath('//select[@name="Screening/Technique[]"]/option[text()="array for Comparative Genomic Hybridisation"]'));
        $option->click();
        $option = $this->driver->findElement(WebDriverBy::xpath('//select[@name="Screening/Technique[]"]/option[text()="array for resequencing"]'));
        $option->click();
        $option = $this->driver->findElement(WebDriverBy::xpath('//select[@name="Screening/Technique[]"]/option[text()="array for SNP typing"]'));
        $option->click();
        $option = $this->driver->findElement(WebDriverBy::xpath('//select[@name="genes[]"]/option[text()="IVD (isovaleryl-CoA dehydrogenase)"]'));
        $option->click();
        $this->check(WebDriverBy::name("variants_found"));
        $option = $this->driver->findElement(WebDriverBy::xpath('//select[@name="owned_by"]/option[text()="Test Owner (#00006)"]'));
        $option->click();
        $element = $this->driver->findElement(WebDriverBy::xpath("//input[@value='Create screening information entry']"));
        $element->click();
        
        $this->assertEquals("Successfully created the screening entry!", $this->driver->findElement(WebDriverBy::cssSelector("table[class=info]"))->getText());
    }
}
