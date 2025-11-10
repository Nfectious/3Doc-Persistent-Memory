# 1) Install deps
sudo apt update
sudo apt install -y inotify-tools jq

# 2) Deploy script + make executable
sudo mkdir -p /opt/valkyrie
sudo chown -R valkyrie:valkyrie /opt/valkyrie
sudo tee /opt/valkyrie/inbox_watcher.sh > /dev/null <<'SCRIPT'
...paste the watcher script above...
SCRIPT
sudo chmod +x /opt/valkyrie/inbox_watcher.sh
sudo chown valkyrie:valkyrie /opt/valkyrie/inbox_watcher.sh

# 3) Add systemd unit
sudo tee /etc/systemd/system/valkyrie-watcher.service > /dev/null <<'UNIT'
...paste the unit above...
UNIT

sudo systemctl daemon-reload
sudo systemctl enable --now valkyrie-watcher
sudo journalctl -u valkyrie-watcher -f