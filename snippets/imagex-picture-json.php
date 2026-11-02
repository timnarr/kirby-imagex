<?php

declare(strict_types = 1);

use TimNarr\Imagex;

// Picks the known options from the snippet's variables; Imagex applies the defaults
$imagex = Imagex::fromSnippetData(get_defined_vars());

// The structure is defined by Imagex::toArray()
echo json_encode($imagex, JSON_UNESCAPED_SLASHES);
