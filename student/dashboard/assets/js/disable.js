window.alert = function(message, timeout=null){
	const alert = document.createElement('div');
	const alertButton = document.createElement('button');
	alertButton.innerText = 'X';
	alert.classList.add('alert');
	alert.setAttribute('style', `
		font-family: 'Poppins', sans-serif;
		position:fixed;
		left: 90%;
		top: 2%;
		padding: 1px;
		width: 16%;
		border-radius: 5px 0 0 5px;
		display:flex;
		background: #ffdb9b;
		color: #ce8500;
		flex-direction:column;
		transform: translateX(-50%);
	`);
	alertButton.setAttribute('style',`
		border: 1px solid #ffd080;
		background: #ffd080;
		color: #ce8500;
		font-size: 19px;
		border-radius: 0 5px 5px 0;
		padding: 8.6px;
		top: -2.5%;
		position: absolute;
		margin-left: 99%;
		cursor: pointer;
	`);
alert.innerHTML = `<span style="padding:10px">${message}</span>`;
alert.appendChild(alertButton);
alertButton.addEventListener('click', (e)=>{
	alert.remove();
});
if(timeout != null){
	setTimeout(()=>{
		if(alert) alert.remove();
	}, Number(timeout))
}
document.body.appendChild(alert);

}
