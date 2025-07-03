/**
 * 初始化音频可视化器
 * @param {HTMLAudioElement} audio - 音频元素
 * @param {HTMLCanvasElement} canvas - 画布元素
 * @param {string} fileURL - 音频文件URL
 */
function initAudioVisualizer(audio, canvas, fileURL) {
    const ctx = canvas.getContext('2d');
    const COOKIE_KEY = 'audioVisualizerStyle';

    /**
     * 可视化样式配置
     */
    const visualizerStyles = [
        { id: 'f106cef4-0876-4448-96f9-0fe16edd3103', name: '渐变柱状图', description: '经典渐变彩色柱状频谱' },
        { id: '53678c69-3529-4dad-9336-9e50d5329496', name: '瀑布频谱', description: '时域流动的频谱瀑布' },
        { id: 'e435f508-331f-4bc1-9cea-8dd5f2ce0f49', name: '频谱雷达', description: '雷达扫描显示频率信号' },
        { id: '29f4acf0-1842-4db3-ab9e-945dd57cc28e', name: '频谱矩阵雨', description: '黑客帝国风格数字雨' },
        { id: '2f81d155-0d72-44e1-a18a-8ce581a829a4', name: '3D频谱波浪', description: '3D波浪表面随频率变化' },
    ];

    /**
     * 状态管理
     */
    const state = {
        currentStyle: getCookie(COOKIE_KEY),
        audioContext: null,
        analyser: null,
        dataArray: null,
        animationId: null,
        segments: 100, // 初始值，会根据性能动态调整
        currentHeights: new Array(100).fill(0),
        targetHeights: new Array(100).fill(0),
        menuVisible: false,
        lastFrameTime: 0,
        transitionProgress: 0,
        prevStyle: null,
        radarCache: null,
        frameTimes: [],
        cleanup: function () {
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
                this.animationId = null;
            }
            if (this.audioContext) {
                this.audioContext.close().catch(e => console.error("AudioContext关闭失败:", e));
                this.audioContext = null;
            }
            this.analyser = null;
            this.dataArray = null;
            this.lastFrameTime = 0;
        }
    };

    /**
     * 特效状态
     */
    const effects = {
        particles: [],
        waterfallData: [],
        soundWavePoints: [],
        ecgHistory: [],
        matrixDrops: [],
        MAX_WATERFALL_ROWS: 100,
        MAX_SOUNDWAVE_POINTS: 50,
        MAX_ECG_HISTORY: 200,
        MAX_PARTICLES: 100
    };

    /**
     * 菜单配置
     */
    const MENU_CONSTANTS = {
        WIDTH: 180,
        ITEM_HEIGHT: 30,
        PADDING: 10,
        BG_COLOR: 'rgba(30, 30, 40, 0.95)',
        BORDER_COLOR: '#34e89e',
        TEXT_COLOR: '#ffffff',
        HIGHLIGHT_COLOR: 'rgba(52, 232, 158, 0.3)',
        SELECTED_COLOR: '#34e89e'
    };

    // 初始化函数
    function init() {
        resizeCanvas();
        setupAudio();
        setupEventListeners();
        initMatrixDrops();
        window.addEventListener('beforeunload', () => state.cleanup());
    }

    // 设置Canvas尺寸
    function resizeCanvas() {
        canvas.width = Math.max(canvas.offsetWidth || 300, 100);
        canvas.height = Math.max(canvas.offsetHeight || 150, 100);
        state.currentHeights = new Array(state.segments).fill(0);
        state.targetHeights = new Array(state.segments).fill(0);
        state.radarCache = null;
        initMatrixDrops();
    }

    // 设置音频
    function setupAudio() {
        audio.src = fileURL;
        audio.addEventListener('play', initAudioContext);
        audio.addEventListener('pause', startDecay);
        audio.addEventListener('ended', startDecay);
    }

    // 初始化音频上下文
    function initAudioContext() {
        if (!state.audioContext) {
            try {
                state.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const source = state.audioContext.createMediaElementSource(audio);
                state.analyser = state.audioContext.createAnalyser();
                state.analyser.fftSize = 256;
                source.connect(state.analyser);
                state.analyser.connect(state.audioContext.destination);
                state.dataArray = new Uint8Array(state.analyser.frequencyBinCount);
                startVisualization();
            } catch (e) {
                console.error("音频分析器初始化失败:", e);
                state.cleanup();
            }
        } else {
            startVisualization();
        }
    }

    // 开始可视化
    function startVisualization() {
        if (state.animationId) {
            cancelAnimationFrame(state.animationId);
        }
        state.lastFrameTime = performance.now();
        state.animationId = requestAnimationFrame(drawFrame);
    }

    // 开始衰减效果
    function startDecay() {
        if (!state.animationId) {
            drawFrame();
        }
    }

    // 主绘制循环
    function drawFrame(timestamp) {
        const frameStartTime = performance.now();
        const expectedInterval = 1000 / 60;
        const actualInterval = timestamp - (state.lastFrameTime || timestamp);

        if (actualInterval > expectedInterval * 2) {
            state.lastFrameTime = timestamp;
            state.animationId = requestAnimationFrame(drawFrame);
            return;
        }

        if (!state.lastFrameTime) state.lastFrameTime = timestamp || frameStartTime;
        const deltaTime = (timestamp || frameStartTime) - state.lastFrameTime;
        state.lastFrameTime = timestamp || frameStartTime;

        updateAudioData(deltaTime);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawCurrentVisualizer(deltaTime);

        if (state.menuVisible) {
            drawMenu();
        }

        if (audio.paused && state.currentHeights.every(h => h < 0.1)) {
            cancelAnimationFrame(state.animationId);
            state.animationId = null;
            state.lastFrameTime = 0;
        } else {
            state.animationId = requestAnimationFrame(drawFrame);
        }
    }

    // 更新音频数据
    function updateAudioData(deltaTime) {
        const safeDeltaTime = Math.max(deltaTime || 16, 1);

        if (state.analyser && !audio.paused) {
            state.analyser.getByteFrequencyData(state.dataArray);
            const binsPerSegment = Math.floor(state.analyser.frequencyBinCount / state.segments);

            for (let i = 0; i < state.segments; i++) {
                let sum = 0;
                for (let j = 0; j < binsPerSegment; j++) {
                    sum += state.dataArray[i * binsPerSegment + j];
                }
                state.targetHeights[i] = (sum / binsPerSegment) / 255 * canvas.height;
            }
        } else {
            for (let i = 0; i < state.segments; i++) {
                state.targetHeights[i] = 0;
            }
        }

        const easingFactor = Math.min(0.3 * (safeDeltaTime / 16), 0.3);
        for (let i = 0; i < state.segments; i++) {
            state.currentHeights[i] += (state.targetHeights[i] - state.currentHeights[i]) * easingFactor;
            if (state.currentHeights[i] < 0.1) state.currentHeights[i] = 0;
        }
    }

    // 绘制当前可视化效果
    function drawCurrentVisualizer(deltaTime) {
        if (state.transitionProgress < 1 && state.prevStyle !== null) {
            state.transitionProgress = Math.min(1, state.transitionProgress + deltaTime / 500);
            drawVisualizerStyle(state.prevStyle, 1 - state.transitionProgress);
            drawVisualizerStyle(state.currentStyle, state.transitionProgress);
        } else {
            state.transitionProgress = 0;
            state.prevStyle = null;
            drawVisualizerStyle(state.currentStyle, 1);
        }
    }

    // 绘制特定样式
    function drawVisualizerStyle(styleId, opacity) {
        ctx.save();
        ctx.globalAlpha = opacity;

        switch (styleId) {
            case 'f106cef4-0876-4448-96f9-0fe16edd3103': drawStyleBars(); break;
            case '53678c69-3529-4dad-9336-9e50d5329496': drawStyleWaterfall(); break;
            case 'e435f508-331f-4bc1-9cea-8dd5f2ce0f49': drawStyleRadar(); break;
            case '29f4acf0-1842-4db3-ab9e-945dd57cc28e': drawStyleMatrix(); break;
            case '2f81d155-0d72-44e1-a18a-8ce581a829a4': draw3DWave(); break;
            default: drawStyleBars();
        }

        ctx.restore();
    }

    // 样式1：渐变柱状图
    function drawStyleBars() {
        const barGap = 2;
        const barWidth = (canvas.width - (state.segments - 1) * barGap) / state.segments;
        const gradientCache = {};

        for (let i = 0, x = 0; i < state.segments; i++, x += barWidth + barGap) {
            const barHeight = state.currentHeights[i];
            if (barHeight <= 0) continue;

            if (!gradientCache[barHeight]) {
                const gradient = ctx.createLinearGradient(0, canvas.height, 0, canvas.height - barHeight);
                gradient.addColorStop(0, '#4285f4');
                gradient.addColorStop(1, '#34e89e');
                gradientCache[barHeight] = gradient;
            }

            ctx.fillStyle = gradientCache[barHeight];
            ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
        }
    }

    // 样式2：瀑布频谱
    function drawStyleWaterfall() {
        const segmentWidth = canvas.width / state.segments;
        const rowHeight = 2;
        const fadeSpeed = 0.98;

        const newRow = state.currentHeights.map(h => h * 0.8);
        effects.waterfallData.unshift(newRow);

        if (effects.waterfallData.length > effects.MAX_WATERFALL_ROWS) {
            effects.waterfallData.length = effects.MAX_WATERFALL_ROWS;
        }

        // 直接在主Canvas上绘制背景
        const bgGradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        bgGradient.addColorStop(0, 'rgba(5, 20, 15, 0.8)');
        bgGradient.addColorStop(1, 'rgba(0, 40, 30, 0.9)');
        ctx.fillStyle = bgGradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const timeFactor = Date.now() / 5000;

        for (let row = 0; row < effects.waterfallData.length; row++) {
            const age = row / effects.waterfallData.length;
            const verticalPos = canvas.height - row * rowHeight;
            const alpha = Math.pow(0.2 + 0.8 * (1 - age), 1.5);

            for (let i = 0; i < state.segments; i++) {
                let height = effects.waterfallData[row][i] * Math.pow(fadeSpeed, row);
                if (height < 0.1) continue;

                const hue = (i / state.segments * 120 + timeFactor * 60) % 360;
                ctx.fillStyle = `hsla(${hue}, 80%, 60%, ${alpha})`;

                const width = segmentWidth * (0.7 + 0.3 * Math.sin(timeFactor + i * 0.1));
                ctx.fillRect(
                    i * segmentWidth + (segmentWidth - width) / 2,
                    verticalPos - height * 0.5,
                    width,
                    rowHeight * (0.5 + height / canvas.height * 3)
                );
            }
        }
    }

    // 样式3：频谱雷达
    function drawStyleRadar() {
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const maxRadius = Math.min(canvas.width, canvas.height) * 0.4;

        // 直接在主Canvas上绘制静态元素
        ctx.strokeStyle = 'rgba(0, 255, 0, 0.2)';
        ctx.lineWidth = 1;

        // 绘制雷达环
        for (let r = 0.2; r <= 1; r += 0.2) {
            ctx.beginPath();
            ctx.arc(centerX, centerY, maxRadius * r, 0, Math.PI * 2);
            ctx.stroke();
        }

        // 绘制雷达线
        for (let i = 0; i < 8; i++) {
            const angle = (i / 8) * Math.PI * 2;
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(
                centerX + Math.cos(angle) * maxRadius,
                centerY + Math.sin(angle) * maxRadius
            );
            ctx.stroke();
        }

        // 雷达数据点
        for (let i = 0; i < state.segments; i++) {
            const angle = (i / state.segments) * Math.PI * 2;
            const heightNorm = state.currentHeights[i] / canvas.height;
            const radius = maxRadius * heightNorm;

            if (radius <= 5) continue;

            const x = centerX + Math.cos(angle) * radius;
            const y = centerY + Math.sin(angle) * radius;

            ctx.strokeStyle = `hsla(${i * 360 / state.segments}, 100%, 50%, 0.3)`;
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(x, y);
            ctx.stroke();

            ctx.fillStyle = `hsl(${i * 360 / state.segments}, 100%, 50%)`;
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    // 样式4：频谱矩阵雨
    function drawStyleMatrix() {
        ctx.fillStyle = 'rgba(0, 20, 0, 0.1)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.font = '16px monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';

        let lastColor = null;

        effects.matrixDrops.forEach(drop => {
            drop.y += drop.speed;

            const colIndex = Math.floor(drop.x / 20);
            const heightNorm = state.currentHeights[colIndex % state.segments] / canvas.height;

            if (Math.random() < 0.05 + heightNorm * 0.25) {
                const charType = Math.random();
                let char;
                if (charType < 0.7) {
                    char = String.fromCharCode(0x30A0 + Math.floor(Math.random() * 96));
                } else if (charType < 0.9) {
                    char = Math.floor(Math.random() * 10);
                } else {
                    char = String.fromCharCode(0x41 + Math.floor(Math.random() * 26));
                }
                drop.chars.unshift(char);
                if (drop.chars.length > drop.length) drop.chars.pop();
            }

            drop.chars.forEach((char, i) => {
                const alpha = 1 - i / drop.chars.length;
                const green = 50 + Math.floor(205 * alpha);
                const color = i === 0 ? 'rgb(0, 255, 0)' : `rgba(0, ${green}, 0, ${alpha})`;

                if (color !== lastColor) {
                    ctx.fillStyle = color;
                    lastColor = color;
                }

                ctx.fillText(char, drop.x, drop.y - i * 20);
            });

            if (drop.y - drop.chars.length * 20 > canvas.height) {
                drop.y = -20 * Math.random();
                drop.chars = [];
                drop.speed = 2 + Math.random() * 5 + heightNorm * 10;
            }
        });
    }

    // 样式5：3D频谱波浪
    function draw3DWave() {
        const cols = Math.min(30, Math.floor(canvas.width / 30));
        const rows = Math.min(15, Math.floor(canvas.height / 30));
        const cellWidth = canvas.width / cols;
        const cellHeight = canvas.height / rows;

        const timeFactor = Date.now() / 500;
        const segmentsRatio = state.segments / (rows * cols);

        const grid = [];
        for (let i = 0; i < rows; i++) {
            grid[i] = [];
            for (let j = 0; j < cols; j++) {
                const x = j * cellWidth;
                const y = i * cellHeight;
                const index = Math.floor((i * cols + j) * segmentsRatio);
                const height = state.currentHeights[index] * 0.8;
                const waveHeight = height * Math.sin(timeFactor + x * 0.01 + y * 0.01);

                grid[i][j] = { x, y, height, waveHeight, index };
            }
        }

        ctx.beginPath();
        for (let i = 0; i < rows; i++) {
            for (let j = 0; j < cols - 1; j++) {
                const current = grid[i][j];
                const next = grid[i][j + 1];

                ctx.moveTo(current.x, current.y - current.waveHeight);
                ctx.lineTo(next.x, next.y - next.waveHeight);
            }
        }
        ctx.strokeStyle = 'rgba(100, 255, 255, 0.5)';
        ctx.lineWidth = 1;
        ctx.stroke();

        ctx.beginPath();
        for (let i = 0; i < rows - 1; i++) {
            for (let j = 0; j < cols; j++) {
                const current = grid[i][j];
                const next = grid[i + 1][j];

                ctx.moveTo(current.x, current.y - current.waveHeight);
                ctx.lineTo(next.x, next.y - next.waveHeight);
            }
        }
        ctx.stroke();

        for (let i = 0; i < rows; i++) {
            for (let j = 0; j < cols; j++) {
                const point = grid[i][j];
                if (point.height > 10) {
                    ctx.fillStyle = `hsl(${point.index * 360 / state.segments}, 100%, 50%)`;
                    ctx.beginPath();
                    ctx.arc(point.x, point.y - point.waveHeight, 2, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
        }
    }

    // 初始化矩阵雨滴
    function initMatrixDrops() {
        effects.matrixDrops = [];
        const cols = Math.floor(canvas.width / 20);
        for (let i = 0; i < cols; i++) {
            effects.matrixDrops.push({
                x: i * 20 + 10,
                y: Math.random() * -canvas.height,
                speed: 2 + Math.random() * 5,
                length: 5 + Math.random() * 10,
                chars: []
            });
        }
    }

    // 切换菜单显示状态
    function toggleMenu(x, y) {
        state.menuVisible = !state.menuVisible;
        if (state.menuVisible) {
            const rect = canvas.getBoundingClientRect();
            state.menuPosition = {
                x: Math.min(x - rect.left, canvas.width - MENU_CONSTANTS.WIDTH - MENU_CONSTANTS.PADDING),
                y: Math.min(y - rect.top, canvas.height - visualizerStyles.length * MENU_CONSTANTS.ITEM_HEIGHT - MENU_CONSTANTS.PADDING)
            };
        }
    }

    // 设置事件监听器
    function setupEventListeners() {
        window.addEventListener('resize', () => {
            drawFrame();
        });

        canvas.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            toggleMenu(e.clientX, e.clientY);
        });

        canvas.addEventListener('click', (e) => {
            if (!state.menuVisible || !state.menuPosition) return;

            const rect = canvas.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;

            const { x, y } = state.menuPosition;
            const menuHeight = visualizerStyles.length * MENU_CONSTANTS.ITEM_HEIGHT;

            if (clickX >= x && clickX <= x + MENU_CONSTANTS.WIDTH &&
                clickY >= y && clickY <= y + menuHeight) {

                const itemIndex = Math.floor((clickY - y) / MENU_CONSTANTS.ITEM_HEIGHT);

                if (itemIndex >= 0 && itemIndex < visualizerStyles.length) {
                    state.prevStyle = state.currentStyle;
                    state.currentStyle = visualizerStyles[itemIndex].id;
                    state.transitionProgress = 0;
                    setCookie(COOKIE_KEY, state.currentStyle, 30);
                }
            }

            state.menuVisible = false;
            drawFrame();
        });

        document.addEventListener('click', (e) => {
            if (state.menuVisible && !e.target.isSameNode(canvas)) {
                state.menuVisible = false;
                drawFrame();
            }
        });
    }

    // 绘制右键菜单
    function drawMenu() {
        if (!state.menuVisible || !state.menuPosition) return;

        const { x, y } = state.menuPosition;
        const menuHeight = visualizerStyles.length * MENU_CONSTANTS.ITEM_HEIGHT;

        ctx.fillStyle = MENU_CONSTANTS.BG_COLOR;
        ctx.fillRect(x, y, MENU_CONSTANTS.WIDTH, menuHeight);

        ctx.strokeStyle = MENU_CONSTANTS.BORDER_COLOR;
        ctx.lineWidth = 2;
        ctx.strokeRect(x, y, MENU_CONSTANTS.WIDTH, menuHeight);

        ctx.font = '14px Arial';
        ctx.textAlign = 'left';

        visualizerStyles.forEach((style, i) => {
            const itemY = y + i * MENU_CONSTANTS.ITEM_HEIGHT;

            if (style.id === state.currentStyle) {
                ctx.fillStyle = MENU_CONSTANTS.HIGHLIGHT_COLOR;
                ctx.fillRect(x, itemY, MENU_CONSTANTS.WIDTH, MENU_CONSTANTS.ITEM_HEIGHT);
            }

            ctx.fillStyle = style.id === state.currentStyle ? MENU_CONSTANTS.SELECTED_COLOR : MENU_CONSTANTS.TEXT_COLOR;
            ctx.fillText(style.name, x + MENU_CONSTANTS.PADDING, itemY + MENU_CONSTANTS.ITEM_HEIGHT / 2 + 5);
        });
    }

    // 初始化
    init();
}