$(function() {
    var app_detail = {
        conditions: {
            id: 0
        },
        init: function() {
            var _self = this;

            $('.comment').on('click', function(){
                $(this).toggleClass('comment-selected');
            });

            $('.comment-edit').on('click', function(e){
                var _id = $(this).attr('_id');      // 获取某个div class或id的属性  attr()
                _self.conditions.id = _id;              // 将获得的_id值(id值)传入condition,id中
                $('#comment_content').val($('#comment_' + _id).text());
                $('#modal_comment_edit').modal('show');
                e.stopPropagation();       //这俩个函数的作用是 .comment-edit click 起作用时，其他的不起作用；不加的话点击会出现模态和class变化
                e.preventDefault();
            });

            $('#save_comment').on('click', function() {
                window.appgrubAjax.request('/admin/app/editcomment', function(data) {
                    $('#modal_comment_edit').modal('hide');
                    swal({
                        title: data,
                        type: "success",
                        showCancelButton: false,
                        confirmButtonClass: 'btn-info',
                        confirmButtonText: '确定'
                    });
                    $('#comment_' + _self.conditions.id).text($('#comment_content').val().trim());     // 保存模态窗输入的内容
                    $('#comment_content').text('');                 //trim() 过滤输入的空格  发送请求后清空模态的内容和传递的id值,不然下一次打开模态窗会出现上次的内容
                    _self.conditions.id = 0;
                }, {
                    id: _self.conditions.id,
                    content: $('#comment_content').val().trim()
                }, 'post');
            });

            $('.comment-delete').on('click', function(e) {
                var _id = $(this).attr('_id');
                _self.conditions.id = _id;              // 需要添加这句不然下面$('#commentInfo_' + _self.conditions.id)执行不成功（id=0未变）
                window.appgrubAjax.request('/admin/app/deletecomment', function(data) {
                        swal({
                            title: data,
                            type: "success",
                            showCancelButton: false,
                            confirmButtonClass: 'btn-info',
                            confirmButtonText: '确定'
                        });
                        $('#commentInfo_' + _self.conditions.id).slideUp(500, function() {
                            $(this).remove();
                        });
                        $('#comment_count').text($('#comment_count').text() - 1);       //ajax传值删除成功后 自动改变评论的数目
                    }, {
                        id: _id
                    },
                    'post');
                e.stopPropagation();
                e.preventDefault();
            });

            $('#batch_delete').on('click', function() {
                var ids = $('#comment_list .comment-selected').map(function() {
                    return +$(this).attr('_id');
                }).get();
                if (ids.length) {
                    swal({
                        title: "确定要删除评论吗?",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn-danger",
                        cancelButtonText: "取消",
                        confirmButtonText: "确定",
                        closeOnConfirm: true
                    }, function() {
                        window.appgrubAjax.request('/admin/app/deletecomment', function(data) {
                            swal({
                                title: data,
                                type: "success",
                                showCancelButton: false,
                                confirmButtonClass: 'btn-info',
                                confirmButtonText: '确定'
                            });
                            $('.comment-selected').slideUp(500, function() {
                                $('.comment-selected').remove();
                            });
                            $('#comment_count').text($('#comment_count').text() - ids.length);
                        }, {
                            id: ids
                        }, 'post');
                    });
                } else {
                    swal({
                        type: 'error',
                        title: "勾选项不能为空",
                        confirmButtonClass: 'btn-danger',
                        confirmButtonText: '确定'
                    });
                }

            });


            $('.info-edit').on('click', function() {
                var appinfo_modal = new window.appgrubBootstrapModal({
                    id: 'appinfo_modal',
                    title: '编辑介绍',
                    css: {
                        width: '800px',
                        "margin-left": '-400px',
                        top: '20%'
                    },
                    content: '<textarea style="height: 300px; width: 95%" id="appinfo_content"></textarea>',
                    callback: {
                        ok: function() {
                            var app_id = $('#app_id').val();
                            var content = $('#appinfo_content').val().trim();
                            window.appgrubAjax.request('/admin/app/editappinfo', function(data) {
                                swal({
                                    title: data,
                                    type: "success",
                                    showCancelButton: false,
                                    confirmButtonClass: 'btn-info',
                                    confirmButtonText: '确定'
                                });
                                $('#app_info').text($('#appinfo_content').val().trim());
                                $('#appinfo_content').text('');
                                appinfo_modal.modal('hide');
                            }, {
                                app_id: app_id,
                                content: content
                            }, 'post');
                        }
                    }
                });
                $('#appinfo_content').val($('#app_info').text().trim());
            });
        }
    };
    app_detail.init();
});