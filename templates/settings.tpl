<table id="notify_check">
	<tr >
		<td class="label">&nbsp;</td><td>{$form.notify_active.html}{$form.notify_active.label}</td>
	</tr>
</table>
<table id="notify" class="form-layout-compressed">
<tr>
	<td class="label">&nbsp;</td>
	<td>
		<div class="crm-accordion-header">Notify Message Settings</div>
		<div class="crm-accordion-body">
				<table>
	 		 <tr>
	 	 	   <td>{$form.notify_honoree_of_honor.html}{$form.notify_honoree_of_honor.label}</td>
	 	 	   <td>{$form.notify_honoree_of_memory.html}{$form.notify_honoree_of_memory.label}</td>
	 	 	 </tr>
	 	 	 <tr>
	 	    <td>{$form.notify_ack_of_honor.html}{$form.notify_ack_of_honor.label}</td>
	 	    <td>{$form.notify_ack_of_memory.html}{$form.notify_ack_of_memory.label}</td>
	    </tr>
	 	 	</table>
			<table id="message-settings">
			<tr><td>{$form.subject.label}</td><td>{$form.subject.html|crmAddClass:huge}<input class="crm-token-selector big" data-field="subject" />
          {help id="id-token-subject" tplFile=$tplFile isAdmin=$isAdmin file="CRM/Contact/Form/Task/Email.hlp"}</td></tr>
      <tr><td>{$form.template.label}</td><td>{$form.template.html}</td></tr>
      </table>
			{include file="CRM/Contact/Form/Task/EmailCommon.tpl" upload=1 noAttach=1}
		</div>
	</td>
</tr> 
</table>
{literal}
<!--<div class="crm-section"  style="padding:5px; font-size:10px"id="available-tokens">
	<div class="crm-accordion-header">Available Tokens</div>
	<div class="crm-accordion-body crm-section" style="padding-left:10px;">
	  <p><span>Donor : </span>{Donor.id}, {Donor.display_name}, {Donor.first_name}, {Donor.last_name}, {Donor.email}, {Donor.amount}</p>
    <p>	<span>Honored : </span>{Honored.id}, {Honored.display_name}, {Honored.first_name}, {Honored.last_name}, {Honored.email}, {Honored.amount}</p>
		<p><span>Next of Kin : </span>{ack.id}, {ack.display_name}, {ack.first_name}, {ack.last_name}, {ack.email}, {ack.amount}</p>
	 </div>-->
</fieldset>
<script>
cj("document").ready(function(){
	cj("#honor").append(cj("#notify_check"));
	cj("#notify").insertAfter("#notify_check");
	cj("#honor tr:last").after("<td></td>");
	cj("#notify-div").insertAfter("#acknowledged");
  cj("#available-tokens").insertBefore("#message-settings");
  cj("#notify").hide();
  cj("#notify_active").on("click", function(){
    if (this.checked) {
       cj("#notify").show();
    	 checkAcknowledged();
    } else {
    	 cj("#notify").hide();
    }
  });     
  if (cj('#notify_active').is(":checked")) {
    cj("#notify").show();
    checkAcknowledged();
  } else {
    cj("#notify").hide();
  }
});
//	cj("#helphtml").hide();
	//cj("#helptext").hide();
</script>
{/literal}

