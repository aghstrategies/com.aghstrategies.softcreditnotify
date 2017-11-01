<div class="crm-section form-item" id="tribute">
	<div class="label">{$form.tribute.label}</div>
	<div class="content">{$form.tribute.html}</div>
  <div class="description"><p>The tribute name is the name for whom the donation will be from on the email. For example "The Jones Family"</p></div>
</div>
{literal}
<script>
cj("#tribute").appendTo(".acknowledge_block-group");
</script>
{/literal}