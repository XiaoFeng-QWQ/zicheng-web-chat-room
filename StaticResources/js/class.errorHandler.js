class ErrorHandler {
    constructor(options = {}) {
        this.options = {
            showStackTrace: true,
            contactSupport: true,
            enableReporting: true,
            appName: '应用程序',
            abortRequests: true, // 是否中止所有网络请求
            suppressSecondaryErrors: true, // 是否抑制后续错误
            ...options
        };

        this.activeRequests = new Set(); // 跟踪所有活跃请求
        this.errorOccurred = false; // 标记是否已发生错误
        this.originalFetch = window.fetch; // 保存原始fetch引用
        this.originalXHRSend = XMLHttpRequest.prototype.send; // 保存原始XHR send

        this.setup();
    }

    setup() {
        // 移除旧监听器
        window.removeEventListener('error', this.handleError);
        window.removeEventListener('unhandledrejection', this.handleRejection);

        // 添加新监听器
        window.addEventListener('error', this.handleError.bind(this));
        window.addEventListener('unhandledrejection', this.handleRejection.bind(this));

        // 拦截网络请求
        this.interceptFetch();
        this.interceptXHR();
    }

    interceptFetch() {
        const self = this;

        window.fetch = async function (...args) {
            if (self.errorOccurred && self.options.abortRequests) {
                return Promise.reject(new Error('请求已被中止：应用发生致命错误'));
            }

            const controller = new AbortController();
            const config = args[1] || {};

            try {
                const request = self.originalFetch(...args, {
                    ...config,
                    signal: controller.signal
                });

                self.activeRequests.add(controller);

                return await request.finally(() => {
                    self.activeRequests.delete(controller);
                });
            } catch (error) {
                if (error.name === 'AbortError') {
                    throw new Error('请求被错误处理器中止');
                }
                throw error;
            }
        };
    }

    interceptXHR() {
        const self = this;
        const originalXHROpen = XMLHttpRequest.prototype.open;

        XMLHttpRequest.prototype.open = function (method, url) {
            if (self.errorOccurred && self.options.abortRequests) {
                throw new Error('XMLHttpRequest已被阻止：应用发生致命错误');
            }

            this._abortController = new AbortController();
            self.activeRequests.add(this._abortController);

            originalXHROpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function (data) {
            if (this._abortController) {
                this.addEventListener('loadend', () => {
                    self.activeRequests.delete(this._abortController);
                });

                this.addEventListener('error', () => {
                    self.activeRequests.delete(this._abortController);
                });
            }

            return self.originalXHRSend.apply(this, arguments);
        };
    }

    abortAllRequests() {
        this.activeRequests.forEach(controller => {
            // 对于fetch请求
            if (controller.abort) {
                controller.abort();
            }
            // 对于XHR请求
            else if (controller._xhr) {
                controller._xhr.abort();
            }
        });
        this.activeRequests.clear();
    }

    handleError(event) {
        // 抑制后续错误
        if (this.errorOccurred && this.options.suppressSecondaryErrors) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        const error = event.error || new Error(event.message);
        this.processError(error);
    }

    handleRejection(event) {
        // 抑制后续错误
        if (this.errorOccurred && this.options.suppressSecondaryErrors) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        const error = event.reason instanceof Error ? event.reason : new Error(String(event.reason));
        this.processError(error);
    }

    processError(error) {
        if (this.errorOccurred) return;
        this.errorOccurred = true;

        // 中止所有网络请求
        if (this.options.abortRequests) {
            this.abortAllRequests();
        }

        this.displayFatalError(error);

        // 发送错误报告（使用sendBeacon确保发送成功）
        if (this.options.enableReporting) {
            this.sendErrorReport(error);
        }
    }

    displayFatalError(error) {
        // 保存当前URL
        const currentUrl = window.location.href;

        // 创建Bootstrap风格的错误页面
        document.body.innerHTML = `
        <div class="container mt-5">
          <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
              <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                  <h2 class="h4 mb-0">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    ${this.options.appName} 遇到错误
                  </h2>
                </div>
                <div class="card-body">
                  <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading">抱歉，出现了问题！</h4>
                    <p>${this.options.appName} 遇到了一个无法恢复的错误。</p>
                    <hr>
                    <p class="mb-0">请尝试刷新页面或稍后再试。</p>
                  </div>
                  
                  <div class="mb-4">
                    <h3 class="h5">错误详情</h3>
                    <ul class="list-group mb-3">
                      <li class="list-group-item">
                        <strong>页面:</strong> <code>${this.escapeHtml(currentUrl)}</code>
                      </li>
                      <li class="list-group-item">
                        <strong>消息:</strong> ${this.escapeHtml(error.message)}
                      </li>
                    </ul>
                    
                    ${this.options.showStackTrace ? `
                      <div class="accordion mb-3">
                        <div class="accordion-item">
                          <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#stackTrace">
                              查看技术详情
                            </button>
                          </h2>
                          <div id="stackTrace" class="accordion-collapse collapse">
                            <div class="accordion-body">
                              <pre class="bg-light p-3 rounded">${this.escapeHtml(error.stack || '无堆栈信息')}</pre>
                            </div>
                          </div>
                        </div>
                      </div>
                    ` : ''}
                  </div>
                  
                  <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <button id="refresh-btn" class="btn btn-primary me-md-2">
                      <i class="bi bi-arrow-clockwise me-1"></i> 刷新页面
                    </button>
                    
                    ${this.options.enableReporting ? `
                      <button id="report-btn" class="btn btn-outline-danger">
                        <i class="bi bi-bug-fill me-1"></i> 报告问题
                      </button>
                    ` : ''}
                  </div>
                  
                  ${this.options.contactSupport ? `
                    <div class="mt-4 text-center text-muted small">
                      <p>如果问题持续存在，请联系我们的支持团队。</p>
                    </div>
                  ` : ''}
                </div>
              </div>
            </div>
          </div>
        </div>
        `;

        // 添加Bootstrap Icons
        this.ensureBootstrapIcons();

        // 初始化Bootstrap组件
        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            new bootstrap.Collapse(document.getElementById('stackTrace'), {
                toggle: false
            });
        }

        // 添加按钮事件
        document.getElementById('refresh-btn').addEventListener('click', () => {
            window.location.reload();
        });

        if (this.options.enableReporting && document.getElementById('report-btn')) {
            document.getElementById('report-btn').addEventListener('click', () => {
                this.sendErrorReport(error);
                const toast = this.createToast('错误报告已发送', '感谢您的反馈！');
                document.body.appendChild(toast);

                if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                    new bootstrap.Toast(toast).show();
                }
            });
        }
    }

    sendErrorReport(error) {
        const reportData = {
            error: error.message,
            stack: error.stack,
            url: window.location.href,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            errorType: error.constructor.name
        };

        // 使用sendBeacon确保在页面卸载前发送
        if (navigator.sendBeacon) {
            try {
                const blob = new Blob([JSON.stringify(reportData)], { type: 'application/json' });
                navigator.sendBeacon('/api/error-log', blob);
            } catch (e) {
                console.error('Failed to send error report:', e);
            }
        } else {
            // 回退方案
            fetch('/api/error-log', {
                method: 'POST',
                body: JSON.stringify(reportData),
                headers: { 'Content-Type': 'application/json' },
                keepalive: true // 确保在页面卸载时也能发送
            }).catch(e => console.error('Failed to send error report:', e));
        }
    }

    createToast(title, message) {
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '11';
        toast.innerHTML = `
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="toast-header bg-success text-white">
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
          <div class="toast-body">
            ${message}
          </div>
        </div>
        `;
        return toast;
    }

    ensureBootstrapIcons() {
        if (!document.querySelector('link[href*="bootstrap-icons"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css';
            document.head.appendChild(link);
        }
    }

    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // 清理方法
    destroy() {
        window.removeEventListener('error', this.handleError);
        window.removeEventListener('unhandledrejection', this.handleRejection);

        // 恢复原始fetch和XHR
        window.fetch = this.originalFetch;
        XMLHttpRequest.prototype.send = this.originalXHRSend;

        this.activeRequests.clear();
    }
}