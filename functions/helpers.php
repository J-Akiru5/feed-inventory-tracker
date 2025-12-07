<?php
function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatMoney($amount)
{
    return '₱ ' . number_format((float)$amount, 2, '.', ',');
}
