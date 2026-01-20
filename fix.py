cat > /tmp/fix_permissions.sh << 'EOF'
#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
START_PATH="${1:-/home/dinkes}"
LOG_FILE="/tmp/permission_fix_$(date +%Y%m%d_%H%M%S).log"
FIX_MODE="${2:-scan}" # scan or fix

echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}🔧 PERMISSION SCANNER & FIXER${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "Start Path: ${YELLOW}$START_PATH${NC}"
echo -e "Mode: ${YELLOW}$FIX_MODE${NC}"
echo -e "Log File: ${YELLOW}$LOG_FILE${NC}"
echo

# Log function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Check if directory is "red" (no execute permission)
is_red_dir() {
    local dir="$1"
    if [ -d "$dir" ]; then
        # Check if owner has execute permission
        if [ -x "$dir" ]; then
            return 1 # Not red (has execute)
        else
            return 0 # Red (no execute)
        fi
    fi
    return 2 # Not a directory
}

# Fix directory permissions
fix_dir() {
    local dir="$1"
    local current_perm=$(stat -c "%a" "$dir" 2>/dev/null)
    
    if [ -z "$current_perm" ]; then
        echo -e "${RED}  ✗ Cannot stat $dir${NC}"
        return 1
    fi
    
    # Check if it's a directory
    if [ ! -d "$dir" ]; then
        return 0
    fi
    
    # Check current permissions
    if [ ! -r "$dir" ] || [ ! -x "$dir" ]; then
        echo -e "${YELLOW}  🔧 Fixing: $dir (was: $current_perm)${NC}"
        
        # Try 755 first (rwxr-xr-x)
        if chmod 755 "$dir" 2>/dev/null; then
            local new_perm=$(stat -c "%a" "$dir")
            echo -e "${GREEN}  ✓ Fixed: $dir (now: $new_perm)${NC}"
            log "FIXED: $dir (was: $current_perm, now: $new_perm)"
            return 0
        else
            # Try 705 if 755 fails
            if chmod 705 "$dir" 2>/dev/null; then
                local new_perm=$(stat -c "%a" "$dir")
                echo -e "${GREEN}  ✓ Fixed (alt): $dir (now: $new_perm)${NC}"
                log "FIXED_ALT: $dir (was: $current_perm, now: $new_perm)"
                return 0
            else
                echo -e "${RED}  ✗ Failed to fix: $dir${NC}"
                log "FAILED: $dir (permission denied)"
                return 1
            fi
        fi
    else
        echo -e "${GREEN}  ✓ OK: $dir (perm: $current_perm)${NC}"
        return 0
    fi
}

# Scan for red directories
scan_red_dirs() {
    local count=0
    echo -e "${BLUE}📁 Scanning for red directories...${NC}"
    
    # Use find to locate directories without execute permission
    while IFS= read -r dir; do
        if is_red_dir "$dir"; then
            echo -e "${RED}🔴 RED DIR: $dir${NC}"
            ((count++))
            
            if [ "$FIX_MODE" = "fix" ]; then
                fix_dir "$dir"
            fi
        fi
    done < <(find "$START_PATH" -type d 2>/dev/null | head -1000)
    
    echo -e "\n${YELLOW}Found $count red directories${NC}"
    return $count
}

# Check important web directories
check_web_dirs() {
    echo -e "\n${BLUE}🌐 Checking web directories...${NC}"
    
    local web_paths=(
        "/home/dinkes/public_html"
        "/home/dinkes/public_html/wp-content"
        "/home/dinkes/public_html/wp-content/uploads"
        "/home/dinkes/public_html/wp-content/plugins"
        "/home/dinkes/public_html/wp-content/themes"
        "/home/dinkes/public_html/pkmsidoharjo"
        "/home/dinkes/public_html/pkmsidoharjo/wp-content"
        "/home/dinkes/public_html/pkmsidoharjo/wp-content/uploads"
    )
    
    for path in "${web_paths[@]}"; do
        if [ -e "$path" ]; then
            if [ -d "$path" ]; then
                if [ -x "$path" ]; then
                    local perm=$(stat -c "%a" "$path")
                    echo -e "${GREEN}✅ $path (perm: $perm)${NC}"
                else
                    echo -e "${RED}🔴 $path (NO EXECUTE)${NC}"
                    if [ "$FIX_MODE" = "fix" ]; then
                        fix_dir "$path"
                    fi
                fi
            else
                echo -e "${YELLOW}📄 $path (is a file)${NC}"
            fi
        else
            echo -e "${BLUE}➖ $path (not found)${NC}"
        fi
    done
}

# Check file permissions
check_file_perms() {
    echo -e "\n${BLUE}📄 Checking file permissions...${NC}"
    
    # Find files with 000 or unusual permissions
    local problematic_files=$(find "$START_PATH" -type f \( -perm /111 -o -perm 000 \) 2>/dev/null | head -20)
    
    if [ -n "$problematic_files" ]; then
        echo -e "${YELLOW}⚠️  Problematic files found:${NC}"
        echo "$problematic_files" | while read file; do
            local perm=$(stat -c "%a" "$file")
            echo -e "  ${RED}$perm${NC} - $file"
            
            if [ "$FIX_MODE" = "fix" ]; then
                echo -n "    Fix? (y/n): "
                read -r answer < /dev/tty
                if [ "$answer" = "y" ]; then
                    chmod 644 "$file" 2>/dev/null && echo -e "    ${GREEN}Fixed to 644${NC}" || echo -e "    ${RED}Failed${NC}"
                fi
            fi
        done
    else
        echo -e "${GREEN}✓ No problematic files found${NC}"
    fi
}

# System info
show_system_info() {
    echo -e "\n${BLUE}🖥️  SYSTEM INFORMATION${NC}"
    echo -e "User: ${YELLOW}$(whoami)${NC}"
    echo -e "UID/GID: ${YELLOW}$(id)${NC}"
    echo -e "Hostname: ${YELLOW}$(hostname)${NC}"
    echo -e "Disk: ${YELLOW}$(df -h /home | tail -1)${NC}"
}

# Main execution
main() {
    echo "Starting scan at $(date)" >> "$LOG_FILE"
    
    show_system_info
    scan_red_dirs
    check_web_dirs
    check_file_perms
    
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${GREEN}✨ SCAN COMPLETED!${NC}"
    echo -e "${BLUE}========================================${NC}"
    
    if [ "$FIX_MODE" = "scan" ]; then
        echo -e "\n${YELLOW}💡 To fix all issues automatically, run:${NC}"
        echo -e "  ${GREEN}bash $0 $START_PATH fix${NC}"
        echo -e "  ${GREEN}OR: bash $0 /home/dinkes/public_html fix${NC}"
    fi
    
    echo -e "\n${BLUE}📋 Log saved to: $LOG_FILE${NC}"
}

# Run main function
main "$@"
EOF
