// 创建一个闭包
(function($) {
	$.fn.vForm = function(options) {
		var command;
		if (jQuery.type(options) === "string") {
			command = options;
			options = {};
		} else {
			command = '';
		}

		return this.each(function() {
			var $form = $(this);

			if (!$form.data('vForm')) {
				var vForm = new $.fn.vForm.form($form, options); // 实例化form类给vForm对象
			} else {
				var vForm = $form.data('vForm');
			}

			return this;
		});
	};

	// 验证方法
	$.fn.vForm.validator = {
		require : function(value, rule) {
			if (value.length > 0) {
				return true;
			} else {
				return false;
			}
		},
		string : function(value, rule) {

			var len = value.length;

			if (rule.lt && len >= rule.lt) {
				return false;
			}

			if (rule.lte && len > rule.lte) {
				return false;
			}

			if (rule.gt && len <= rule.gt) {
				return false;
			}

			if (rule.gte && len < rule.gte) {
				return false;
			}

			return true;
		},
		list : function(value, rule) {
			switch (rule.type) {
			case 'sex':
				var valSelect = [ 'man', 'woman' ];
				break;
			case 'edu':
				var valSelect = [ '0', '1' ]; // 0代表本科,1代表专科;
				break;
			case 'city':
				var valSelect = [ '0', '1' ]; // 0代表北京,1代表上海;
				break;
			case 'companyType':
				var valSelect = [ '0', '1' ]; // 0代表全职,1代表兼职;
				break;
			case 'spaceTime':
				var valSelect = [ '1', '2', '3', '4', '5', '6', '7' ]; // 周一到周天
				break;
			default:
				var valSelect = rule.list;
			}
			for ( var i = 0; i <= valSelect.length; i++) { // 查找在数组里没有
				if (value == valSelect[i]) {
					return true;
				}
			}
			;
			return false;
		},
		password : function(value) {
			return (value.length >= 6 && value.length <= 20);
		},
		email : function(value) {
			if (value.length > 99) {
				return false;
			}
			return /^\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(value);
		},
		number : function(value, rule) {
			if (isNaN(value)) { // 必须事数字
				return false;
			}

			value = parseInt(value); // 判断数字范围

			if (rule.lt && value >= rule.lt) {
				return false;
			}

			if (rule.lte && value > rule.lte) {
				return false;
			}

			if (rule.gt && value <= rule.gt) {
				return false;
			}

			if (rule.gte && value < rule.gte) {
				return false;
			}
			return true;
		},
		callback : function(value, rule) {
			return rule.fn.call(this, value, rule);// {v:'callback',
			// fn:function (value, rule)
			// {return value == 1;}
		},
		comfirm : function(value, rule) {
			if (rule.gt) {
				return value > this.$form.find(rule.target).eq(0).val();
			}
			return value == this.$form.find(rule.target).eq(0).val();

		},
		ajax : function(value, rule) {
			var vField = this, $field = this.$field;
			$.post(rule.url, {
				value : value
			}, function(data) {
				// 字段值被修改，需重新验证
				if (data.value == $field.val()) {
					if (data.isValid) {
						// 验证通过
						$field.data('lastStatus', 'has-success');
						vField.setStatus('has-success');
					} else {
						// 验证不通过
						$field.data('lastStatus', 'has-error');
						var errorMsg;
						if (rule['errorMsg']) {
							errorMsg = rule['errorMsg'];
						} else {
							errorMsg = data.errorMsg;
						}
						vField.setStatus('has-error', errorMsg);
					}
				}

				$field.data('vField-Ajax', false);

			}, 'json');

			return 'waiting';
		}
	};

	/*
	 * 字段类，遍历每个表单字段的验证选项，并绑定onblur()事件
	 */
	$.fn.vForm.field = function($field, $form) {
		$field.data('vField', this);

		var options = {};
		this.$form = $form;
		this.$field = $field;
		this.status = '';
		this.rules = [];

		$fieldOptElm = $field.next("script[type=data]:eq(0)");

		if ($fieldOptElm) {
			options = eval("(" + $fieldOptElm.html() + ")");
			// 遍历验证选项
			for ( var i in options) {
				this[i] = options[i];
			}
		}
		// 过滤验证规则开始
		switch (jQuery.type(this.rules)) {
		case 'string':
			this.rules = [ {
				v : this.rules
			} ];
			break;
		case 'object':
			this.rules = [ this.rules ];
			break;
		case 'array':
			for ( var i in this.rules) {
				var rule = this.rules[i];
				if (jQuery.type(rule) == 'string') {
					this.rules[i] = {
						v : rule
					};
				}
			}
			break;
		}
		// 过滤验证规则结束

		// 绑定事件开始
		var vField = this;

		// $field.change(function() {
		// vField.isValid();
		// });
		$field.blur(function() {
			vField.isValid();
		});
		// 绑定事件结束
	}

	/*
	 * 字段原型属性和方法，isValid用于判断是否通过验证；setStatus用于设置bootstrap样式
	 */

	$.fn.vForm.field.prototype = {
		isValid : function() {
			var $field = this.$field, value = $field.val();
			if ($field.data('lastValue') == value) { // 防止再次验证
				return $field.data('lastStatus');
			}

			this.setStatus();

			var isValid = 'success', msg;
			
			if (this.rules.length) {
				var i, rule;
				validateRules: for (i in this.rules) { // 调用验证方法
					rule = this.rules[i];
					if (!value.length && rule.v != 'require') {
						continue;
					}

					switch ($.fn.vForm.validator[rule.v]
							.call(this, value, rule)) {
					case false:
					case 'error':
						// Error 必须放在最前面
						isValid = 'error';
						msg = rule['errorMsg'];
						break validateRules;
					case 'waiting':
						msg = rule['waitingMsg'];
						isValid = 'waiting';
						break;
					}

				}

				$field.data('lastValue', value);
				$field.data('lastStatus', isValid);

				if (!this.status) {
					this.setStatus(isValid, msg); // 调用Status方法
				}
			}
			return isValid; // 返回验证通过与否
		},

		setStatus : function(status, msg) {
			if (status == this.status)
				return true;

			$container = this.$field.parents('.form-group');
			$tipField = this.$field.parents().next('.vform-help');

			if (this.status) {
				$container.removeClass(this.status); // 变颜色
			}

			if (status) {
				$container.addClass(status); // 变颜色
			}
			this.status = status;
			var meg = '';
			if(typeof(status) != 'undefined'){
				var meg = status.replace('has-','');//过滤has-error
			}
			if (msg) {
				$tipField.html(msg); // 输出错误信息
			} else if (this[meg + 'Msg']) {
				$tipField.html(this[meg + 'Msg']);
			} else {
				$tipField.html('');
			}
		},
		successMsg : '',
		errorMsg : '输入错误',
		warningMsg : '警告',
		infoMsg : '',
		waitingMsg : '正在验证...'
	};

	/*
	 * form 类 初始话form的配置信息，遍历表单字段 ；然后绑定submit事件 $('#login').form();
	 * 
	 * var vForm = $('#login').data('vForm'); vForm.bindEvent();
	 * 
	 */

	$.fn.vForm.form = function($form, options) {

		// 把this数据给$form，名称为vForm
		$form.data('vForm', this);

		// 初始化属性
		this.$form = $form;
		this.vFieldList = [];
		this.isSubmitIng = false;

		// 把配置信息放进来
		for ( var i in options) {
			this[i] = options[i];
		}

		var vForm = this;
		// 实例化表单下的字段开始
		$form
				.find(
						"input[type=text],input[type=password],input[type=radio],select,textarea")
				.each(
						function() {
							var $field = $(this);
							if (!$field.data('vField')) {
								// 遍历字段
								vForm.vFieldList.push(new $.fn.vForm.field(
										$field, $form));
							}
						});
		// 实例化表单下的字段结束

		// 绑定表单事件开始
		$form.submit(function() {
			return vForm.submit();
		});
		// 绑定表单事件结束
	};

	/*
	 * form类的默认属性和方法 : submit是提交方法 ; success是提交成功的方法 ; error是提交错误的方法
	 */
	$.fn.vForm.form.prototype = {
		submit : function() {
			// 防止重复提交
			if (this.isSubmitIng) {
				return false;
			}

			var i, vField, isValid = 'success';

			validateField: for (i in this.vFieldList) {
				vField = this.vFieldList[i];

				switch (vField.isValid()) {
				case 'error':
					// Error 必须放在最前面
					isValid = 'error';
					break validateField;
				case 'waiting':
					isValid = 'waiting';
					break;
				}
			}
			switch (isValid) {
			case 'success':
				var vForm = this;
				$.post(vForm.$form.attr('action'), vForm.$form.serialize(),
						function(data) {
							if (data.isValid) {
								vForm.success(data); // 调用 success 方法
							} else {
								vForm.error(data);
							}
							vForm.isSubmitIng = false;
						}, 'json');
				break;
			case 'error':
				this.isSubmitIng = false;
				break;
			case 'waiting':
				var vForm = this;
				window.setTimeout(function() {
					vForm.$form.submit();
				}, 1000);

				this.isSubmitIng = false;
				break;
			}
			return false;
		},
		success : function(data) {
		},
		error : function(data) {
			if (!data.isValid) {
				for ( var i in data.error) {
					var $field = this.$form.find('[name=' + i + ']:eq(0)');
					if ($field) {
						$field.data('vField').setStatus('error', data.error[i]);
					}
				}
			}
		}
	};
	// 闭包结束
})(jQuery);