<?php
namespace eTools\Tests\Utils;

class SlugifyTest extends \PHPUnit_Framework_TestCase {
	public function testCleanTeamName() {
		$slugify = new \eTools\Utils\Slugify();

		// Test standard name should be strtolower()'d
		$this->assertEquals('madman', $slugify->cleanTeamName('Madman'));

		// Brackets should be removed
		$this->assertEquals('ipt-madman', $slugify->cleanTeamName('<[{(IPT)}]>Madman'));

		// Spaces at the start/end of string should be trimmed
		$this->assertEquals('madman', $slugify->cleanTeamName(' madman '));

		// Spaces inside the string should be converted to hyphens
		$this->assertEquals('m-a-d-m-a-n', $slugify->cleanTeamName(' m a d m a n '));

		// Certain accented characters should be transliterated into standard ASCII
		$this->assertEquals("aaeeeiu", $slugify->cleanTeamName("àâéèêîù"));

		// Certain invalid characters should be removed, replaced with spaces, which are trimmed away to just a hyphen
		$this->assertEquals("-", $slugify->cleanTeamName("'-:!?;,`|."));

		// If all characters are removed, the name is a single hyphen
		$this->assertEquals("-", $slugify->cleanTeamName("[]"));

		// If the name is entirely spaces, it is replaced with a single hyphen
		$this->assertEquals("-", $slugify->cleanTeamName('          '));

		// preg_replace removes any non-ASCII or numeric character with nothing
		$this->assertEquals("-", $slugify->cleanTeamName('#$%^&*~|\'\\'));
	}
}