#!/bin/bash
#
# Valkyrie Memory System - Installation Script
# Version: 4.2.0
#
# This script installs Valkyrie Memory System on a Linux server
# Supports: Ubuntu 20.04+, Debian 10+
#
# Usage: sudo bash install.sh
#

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ========================================
# CONFIGURATION - UPDATE THESE VALUES
# ========================================
INSTALL_DIR="/opt/valkyrie"
WEB_DIR="/var/www/valkyrie"
MEMORY_DIR="$INSTALL_DIR/memory"
USER="valkyrie"
GROUP="valkyrie"

# TODO: Update these placeholders before running
DOMAIN="aimem.bsapservices.com"  # Change to your actual domain
PHP_VERSION="8.3.6"               # Change if using different PHP version (e.g., 8.1, 8.2)

# Default projects to create (customize as needed)
DEFAULT_PROJECTS="ebay_autods_bot truthvault cryptosite mytradesgui progressive infolookup"  # Space-separated list

# Functions
print_header() {
    echo -e "${BLUE}"
    echo "╔══════════════════════════════════════════════════════╗"
    echo "║                                                      ║"
    echo "║       🩸 VALKYRIE MEMORY SYSTEM v4.2.0              ║"
    echo "║          Installation Script                         ║"
    echo "║                                                      ║"
    echo "╚══════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_step() {
    echo -e "${GREEN}▶ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

check_os() {
    print_step "Checking operating system..."
    
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
        VERSION=$VERSION_ID
        print_success "Detected: $OS $VERSION"
    else
        print_error "Cannot detect operating system"
        exit 1
    fi
}

check_dependencies() {
    print_step "Checking dependencies..."

    MISSING=()

    # Check for required commands
    command -v nginx >/dev/null 2>&1 || MISSING+=("nginx")
    command -v php >/dev/null 2>&1 || MISSING+=("php${PHP_VERSION}-fpm php${PHP_VERSION}-cli")

    if [ ${#MISSING[@]} -gt 0 ]; then
        print_warning "Missing dependencies: ${MISSING[*]}"
        print_step "Installing dependencies..."

        apt update
        apt install -y nginx php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-json php${PHP_VERSION}-mbstring

        print_success "Dependencies installed"
    else
        print_success "All dependencies present"
    fi
}

create_user() {
    print_step "Creating system user..."
    
    if id "$USER" &>/dev/null; then
        print_success "User $USER already exists"
    else
        useradd -r -s /bin/bash -d "$INSTALL_DIR" -m "$USER"
        print_success "Created user: $USER"
    fi
}

create_directories() {
    print_step "Creating directory structure..."

    # Create main directories
    mkdir -p "$INSTALL_DIR"
    mkdir -p "$MEMORY_DIR/projects"
    mkdir -p "$MEMORY_DIR/global"
    mkdir -p "$MEMORY_DIR/_incoming"
    mkdir -p "$MEMORY_DIR/_archive"
    mkdir -p "$WEB_DIR"

    # Create default projects from configuration
    for project in $DEFAULT_PROJECTS; do
        mkdir -p "$MEMORY_DIR/projects/$project"

        # Initialize memory files
        cat > "$MEMORY_DIR/projects/$project/PROJECT_MEMORY.md" <<EOF
# PROJECT: $project

Created: $(date '+%Y-%m-%d %H:%M:%S')

## Overview

EOF

        cat > "$MEMORY_DIR/projects/$project/INSIGHTS_LOG.md" <<EOF
# INSIGHTS LOG: $project

EOF

        cat > "$MEMORY_DIR/projects/$project/NEXT_ACTIONS.md" <<EOF
# NEXT ACTIONS: $project

EOF
    done
    
    # Initialize global memory
    cat > "$MEMORY_DIR/global/PROJECT_MEMORY.md" <<EOF
# GLOBAL MEMORY

Cross-project context and shared information.

EOF
    
    cat > "$MEMORY_DIR/global/INSIGHTS_LOG.md" <<EOF
# GLOBAL INSIGHTS LOG

EOF
    
    cat > "$MEMORY_DIR/global/NEXT_ACTIONS.md" <<EOF
# GLOBAL NEXT ACTIONS

EOF
    
    print_success "Directory structure created"
}

install_files() {
    print_step "Installing application files..."
    
    # Copy web files
    cp -r src/web/* "$WEB_DIR/"
    
    # Copy CLI tools
    cp -r src/cli/* "$INSTALL_DIR/"
    chmod +x "$INSTALL_DIR/"*.sh 2>/dev/null || true
    
    print_success "Application files installed"
}

set_permissions() {
    print_step "Setting permissions..."
    
    # Memory directory - full access for valkyrie user
    chown -R $USER:$GROUP "$MEMORY_DIR"
    chmod -R 750 "$MEMORY_DIR"
    
    # Install directory
    chown -R $USER:$GROUP "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    
    # Web directory - read-only for web server
    chown -R www-data:www-data "$WEB_DIR"
    chmod -R 755 "$WEB_DIR"
    
    print_success "Permissions set"
}

configure_nginx() {
    print_step "Configuring Nginx..."

    cat > /etc/nginx/sites-available/valkyrie <<EOF
server {
    listen 80;
    server_name ${DOMAIN};

    root /var/www/valkyrie;
    index index.html;

    location / {
        try_files \$uri \$uri/ =404;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

    location = /api.php {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    # Increase upload limits for file uploads
    client_max_body_size 10M;
}
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/valkyrie /etc/nginx/sites-enabled/valkyrie

    # Test configuration
    nginx -t

    # Restart Nginx
    systemctl restart nginx
    systemctl restart php${PHP_VERSION}-fpm

    print_success "Nginx configured and restarted"
}

create_cli_tool() {
    print_step "Creating CLI tool..."
    
    cat > /usr/local/bin/vproject <<'EOFCLI'
#!/bin/bash
# Valkyrie Project Manager CLI

MEMORY_BASE="/opt/valkyrie/memory"

case "$1" in
    list)
        echo "📁 Available Projects:"
        ls -1 "$MEMORY_BASE/projects/" 2>/dev/null | sed 's/^/  - /'
        ;;
    create)
        if [ -z "$2" ]; then
            echo "Usage: vproject create <project_name>"
            exit 1
        fi
        PROJECT="$2"
        mkdir -p "$MEMORY_BASE/projects/$PROJECT"
        echo "# PROJECT: $PROJECT" > "$MEMORY_BASE/projects/$PROJECT/PROJECT_MEMORY.md"
        echo "# INSIGHTS: $PROJECT" > "$MEMORY_BASE/projects/$PROJECT/INSIGHTS_LOG.md"
        echo "# ACTIONS: $PROJECT" > "$MEMORY_BASE/projects/$PROJECT/NEXT_ACTIONS.md"
        echo "✓ Created project: $PROJECT"
        ;;
    view)
        if [ -z "$2" ]; then
            echo "Usage: vproject view <project_name>"
            exit 1
        fi
        PROJECT="$2"
        cat "$MEMORY_BASE/projects/$PROJECT/PROJECT_MEMORY.md" 2>/dev/null || echo "Project not found"
        ;;
    help|*)
        echo "Valkyrie Project Manager"
        echo ""
        echo "Usage: vproject <command> [arguments]"
        echo ""
        echo "Commands:"
        echo "  list              List all projects"
        echo "  create <name>     Create new project"
        echo "  view <name>       View project memory"
        echo "  help              Show this help"
        ;;
esac
EOFCLI
    
    chmod +x /usr/local/bin/vproject
    
    print_success "CLI tool created: vproject"
}

print_completion() {
    echo ""
    echo -e "${GREEN}"
    echo "╔══════════════════════════════════════════════════════╗"
    echo "║                                                      ║"
    echo "║   ✓ INSTALLATION COMPLETE                           ║"
    echo "║                                                      ║"
    echo "╚══════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo ""
    echo "📍 Web Interface: http://${DOMAIN}"
    echo "📍 Or by IP: http://$(hostname -I | awk '{print $1}')"
    echo ""
    echo "🔧 Next Steps:"
    echo "  1. Configure DNS to point ${DOMAIN} to this server"
    echo "  2. Access web interface"
    echo "  3. Select a project"
    echo "  4. Copy memory to AI session"
    echo ""
    echo "🐳 For Nextcloud Docker integration, see:"
    echo "   2025-11-09_3Doc_Valkyrie_Memory_V4-2/NEXTCLOUD_DOCKER_INTEGRATION.md"
    echo ""
    echo "⚡ Quick Commands:"
    echo "  vproject list           - List all projects"
    echo "  vproject create <name>  - Create new project"
    echo "  vproject view <name>    - View project memory"
    echo ""
}

# Main Installation
main() {
    print_header
    
    check_root
    check_os
    check_dependencies
    create_user
    create_directories
    install_files
    set_permissions
    configure_nginx
    create_cli_tool
    
    print_completion
}

# Run installation
main "$@"
