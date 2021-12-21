<?php
$config = FusionsPim\PhpCsFixer\Factory::fromDefaults([
    'group_import' => false, // Currently broken for root classes
]);

return $config->setFinder(
    $config->getFinder()
        ->notName('rector.php')
);
