#!/bin/bash
# Post-deploy fix for PHP 8.3 and Drush in stage.bwtf.com

# 1. Create PHP wrapper
cat > /home/bwtfcom/stage.bwtf.com/php <<'EOF'
#!/bin/bash
exec /opt/cpanel/ea-php83/root/usr/bin/php "$@"
EOF
chmod +x /home/bwtfcom/stage.bwtf.com/php

# 2. Create Drush symlink
ln -sf ./vendor/drush/drush/drush.php /home/bwtfcom/stage.bwtf.com/drush

# 3. Ensure PATH and alias in ~/.bashrc
if ! grep -q '/home/bwtfcom/stage.bwtf.com:$PATH' ~/.bashrc; then
  echo 'export PATH="/home/bwtfcom/stage.bwtf.com:$PATH"' >> ~/.bashrc
fi
if ! grep -q 'alias drush="php /home/bwtfcom/stage.bwtf.com/drush"' ~/.bashrc; then
  echo 'alias drush="php /home/bwtfcom/stage.bwtf.com/drush"' >> ~/.bashrc
fi

echo "PHP 8.3 wrapper, Drush symlink, and shell config updated. Please open a new terminal session."
