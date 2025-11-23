---
description: Deploy the application to the production server
---

1. Sync files to the server
// turbo
rsync -avz --exclude '.git' --exclude '.agent' --exclude '.credentials' --exclude 'node_modules' --exclude 'custom_apps.json' ./ followma_viralmagical:~/public_html/