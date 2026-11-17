<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\FlatFilePagePersistence;

/**
 * @coversDefaultClass \herbie\FlatFilePagePersistence
 */
class FlatFilePagePersistenceTest extends Unit
{
    /**
     * @covers ::parseFileContent
     * @group parseFileContent
     */
    public function testBodyWithSingleSeparator(): void
    {
        $content = <<<'MD'
---
title: Test
---

# Header

Some text

---

More content
MD;

        [$yaml, $segments] = FlatFilePagePersistence::parseFileContent($content);

        $this->assertStringContainsString('title: Test', $yaml);
        $this->assertStringContainsString('Some text', $segments['default']);
        $this->assertStringContainsString('More content', $segments['default']);
    }

    /**
     * @covers ::parseFileContent
     * @group parseFileContent
     */
    public function testBodyWithMultipleSeparators(): void
    {
        $content = <<<'MD'
---
title: Multi
---

# Header

Section 1

---

Section 2

---

Section 3

---

Final section
MD;

        [$yaml, $segments] = FlatFilePagePersistence::parseFileContent($content);

        $this->assertStringContainsString('title: Multi', $yaml);
        $this->assertStringContainsString('Section 1', $segments['default']);
        $this->assertStringContainsString('Section 2', $segments['default']);
        $this->assertStringContainsString('Section 3', $segments['default']);
        $this->assertStringContainsString('Final section', $segments['default']);
    }

    /**
     * @covers ::parseFileContent
     * @group parseFileContent
     */
    public function testFencedCodeBlockWithSegmentSeparator(): void
    {
        $content = <<<'MD'
---
title: Test
---

# Header

```
--- sidebar ---
```

Some text
MD;

        [$yaml, $segments] = FlatFilePagePersistence::parseFileContent($content);

        $this->assertStringContainsString('title: Test', $yaml);
        $this->assertArrayNotHasKey('sidebar', $segments);
        $this->assertStringContainsString('--- sidebar ---', $segments['default']);
    }

    /**
     * @covers ::parseFileContent
     * @group parseFileContent
     */
    public function testTildeFencedCodeBlockWithSegmentSeparator(): void
    {
        $content = <<<'MD'
---
title: Test
---

# Header

~~~
--- sidebar ---
~~~

Some text
MD;

        [$yaml, $segments] = FlatFilePagePersistence::parseFileContent($content);

        $this->assertStringContainsString('title: Test', $yaml);
        $this->assertArrayNotHasKey('sidebar', $segments);
        $this->assertStringContainsString('--- sidebar ---', $segments['default']);
    }

    /**
     * @covers ::parseFileContent
     * @group parseFileContent
     */
    public function testImprintFile(): void
    {
        $content = <<<'MD'
---
title: Imprint
---

# Imprint

## Contact

Info here

---

## Disclaimer

No liability.
MD;

        [$yaml, $segments] = FlatFilePagePersistence::parseFileContent($content);

        $this->assertStringContainsString('title: Imprint', $yaml);
        $this->assertStringContainsString('## Contact', $segments['default']);
        $this->assertStringContainsString('## Disclaimer', $segments['default']);
    }
}
