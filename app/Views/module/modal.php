<!-- 模态窗 -->
<div class="modal fade" id="logoutModal" aria-labelledby="logoutModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">确认退出</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                确定要离开“<?= $SystemSetting->getSetting('site_name') ?>”吗？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmLogout">确认</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="filePreviewModal" aria-labelledby="filePreviewModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filePreviewModalLabel">文件预览 <span id="filePreviewFileInfo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="filePreviewContent" style="overflow: hiden; max-height: 100vh;"></div>
        </div>
    </div>
</div>
<div class="modal fade" id="cssCustomizeModal" aria-labelledby="cssCustomizeModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cssCustomizeModalLabel">界面自定义设置</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <form id="cssCustomizeForm">
                    <div class="mb-3">
                        <h6 class="mb-3">自定义CSS</small></h6>
                        <textarea class="form-control font-monospace" id="customCss" rows="5" placeholder="输入自定义CSS代码..."></textarea>
                        <div class="form-text">例如: .chat-message { border-radius: 15px; }</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="resetCssSettings">恢复默认</button>
                <button type="button" class="btn btn-primary" id="saveCssSettings">保存设置</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="fileManagerModal" aria-labelledby="fileManagerModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileManagerModalLabel">所有用户上传的文件</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex justify-content-between align-items-center mb-3 py-2">
                    <div>
                        <i class="bi bi-file-earmark-text me-2"></i>
                        <span id="totalFilesCount">0</span> 个文件
                    </div>
                    <div>
                        <i class="bi bi-hdd me-2"></i>
                        总大小: <span id="totalFilesSize">0 KB</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <div class="input-group" style="max-width: 300px;">
                        <input type="text" id="fileSearchInput" class="form-control" placeholder="搜索文件...">
                        <button class="btn btn-outline-secondary" type="button" id="fileSearchBtn">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="fileSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            排序方式
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="fileSortDropdown">
                            <li><a class="dropdown-item sort-option" href="#" data-sort="created_at">上传时间</a></li>
                            <li><a class="dropdown-item sort-option" href="#" data-sort="file_name">文件名</a></li>
                            <li><a class="dropdown-item sort-option" href="#" data-sort="file_size">文件大小</a></li>
                        </ul>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>类型</th>
                                <th>大小</th>
                                <th>上传时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="fileListBody">
                            <!-- 文件列表将通过JS动态加载 -->
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted" id="filePaginationInfo"></div>
                    <nav aria-label="File pagination">
                        <ul class="pagination" id="filePagination">
                            <!-- 分页将通过JS动态生成 -->
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<!-- 公告 -->
<div class="modal fade" id="noticeListModal" aria-labelledby="noticeListModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="noticeListModalLabel">系统公告列表</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="15%">类型</th>
                                <th width="25%">标题</th>
                                <th width="20%">发布者</th>
                                <th width="20%">发布时间</th>
                                <th width="10%">状态</th>
                                <th width="10%">操作</th>
                            </tr>
                        </thead>
                        <tbody id="noticeListBody">
                            <!-- 公告列表将通过JS动态加载 -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="noticeDetailModal" aria-labelledby="noticeDetailModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="noticeDetailHeader">
                <h5 class="modal-title" id="noticeDetailModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="noticeDetailContent">
                <!-- 公告内容将通过JS动态加载 -->
            </div>
            <div class="modal-footer" id="noticeDetailFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary confirm-read" id="confirmReadBtn">标记为已读</button>
            </div>
        </div>
    </div>
</div>