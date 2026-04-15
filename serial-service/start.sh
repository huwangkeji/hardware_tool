#!/bin/bash
# Node.js 串口服务启动脚本 (Linux/macOS)

echo "================================"
echo "  硬件调试工具 - 串口服务"
echo "================================"
echo ""

# 检查Node.js是否安装
if ! command -v node &> /dev/null; then
    echo "❌ 错误: 未找到 Node.js"
    echo "请先安装 Node.js 14.0 或更高版本"
    echo "下载地址: https://nodejs.org/"
    exit 1
fi

# 显示Node.js版本
echo "Node.js 版本: $(node --version)"
echo ""

# 检查依赖是否已安装
if [ ! -d "node_modules" ]; then
    echo "📦 首次运行,正在安装依赖..."
    npm install
    echo ""
fi

# 创建日志目录
if [ ! -d "logs" ]; then
    mkdir -p logs
    echo "📁 已创建日志目录"
fi

echo "🚀 启动串口服务..."
echo "服务地址: ws://localhost:8080"
echo "按 Ctrl+C 停止服务"
echo ""

# 启动服务
node server.js
