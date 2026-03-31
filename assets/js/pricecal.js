const amount = document.querySelector('#amount'),
qty = document.querySelector('#qty');
gst = document.querySelector('#gst'),
total = document.querySelector('#total');

amount.addEventListener("input", ()=>{
    const amountval = parseFloat(document.querySelector('#amount').value);
    gst.value = (amountval * 0.18).toFixed(2);
    total.value = (amountval + amountval * 0.18).toFixed(2);
})

qty.addEventListener("input", ()=>{

})

total.addEventListener("input", ()=>{
    const totalVal = parseFloat(document.querySelector('#total').value);
    amount.value = (totalVal / 1.18).toFixed(2);
    const amountval = parseFloat(document.querySelector('#amount').value);
    gst.value = (totalVal - amountval).toFixed(2);
})