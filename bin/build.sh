#/bin/bash
git clone https://github.com/SerafimArts/sync-tool.git
cd sync-tool
composer install
cd ../../
php bin/sync-tool/packages sync "subsplit.json" -vvv
rm -rf bin/sync-tool
