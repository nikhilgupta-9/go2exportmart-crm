const custNum = document.querySelector('#custnum'),
numberRegex = /[0-9]/g;


custNum.addEventListener("input", ()=>{
    if(custNum.value !== ""){
        let validatedText = custNum.value.match(numberRegex);
        let finalText = validatedText ? validatedText.join("") : "";
        custNum.value = finalText;
    }
})