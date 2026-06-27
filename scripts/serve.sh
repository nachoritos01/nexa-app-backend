#!/bin/bash
# ===========================================
# SaaS Template - Development Server
# ===========================================

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Detect correct PHP binary
if [ -x "/opt/homebrew/opt/php/bin/php" ]; then
    PHP_BIN="/opt/homebrew/opt/php/bin/php"
elif command -v php &> /dev/null; then
    PHP_BIN="php"
else
    echo "PHP not found. Install with: brew install php"
    exit 1
fi

PHP_VERSION=$($PHP_BIN -v | head -n 1 | cut -d' ' -f2)
PORT=${1:-8000}

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN} SaaS Template - Development Server${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  ${YELLOW}PHP:${NC}     $PHP_VERSION"
echo -e "  ${YELLOW}Port:${NC}    $PORT"
echo -e "  ${YELLOW}URL:${NC}     http://localhost:$PORT"
echo -e "  ${YELLOW}Admin:${NC}   http://localhost:$PORT/admin"
echo -e "  ${YELLOW}API:${NC}     http://localhost:$PORT/api"
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  ${GREEN}Available endpoints:${NC}"
echo "    GET  /api                    -> Health check"
echo "    GET  /api/v1/items           -> List items"
echo "    GET  /api/v1/orders          -> List orders"
echo "    GET  /api/v1/customers       -> List customers"
echo "    GET  /admin                  -> Admin panel"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  Press ${YELLOW}Ctrl+C${NC} to stop the server"
echo ""

# Start server
$PHP_BIN artisan serve --port=$PORT
