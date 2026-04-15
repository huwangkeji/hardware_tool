@echo off
REM Node.js 串口服务启动脚本 (Windows)

echo ================================
echo   硬件调试工具 - 串口服务
echo ================================
echo.

REM 检查Node.js是否安装
where node >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ❌ 错误: 未找到 Node.js
    echo 请先安装 Node.js 14.0 或更高版本
    echo 下载地址: https://nodejs.org/
    pause
    exit /b 1
)

REM 显示Node.js版本
for /f "tokens=*" %%i in ('node --version') do set NODE_VERSION=%%i
echo Node.js 版本: %NODE_VERSION%
echo.

REM 检查依赖是否已安装
if not exist "node_modules" (
    echo 📦 首次运行,正在安装依赖...
    call npm install
    echo.
)

REM 创建日志目录
if not exist "logs" (
    mkdir logs
    echo 📁 已创建日志目录
)

echo 🚀 启动串口服务...
echo 服务地址: ws://localhost:8080
echo 按 Ctrl+C 停止服务
echo.

REM 启动服务
node server.js
