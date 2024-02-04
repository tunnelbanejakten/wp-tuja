<?php foreach ( $rows as $row ) { ?>
<article class="expense-report">
    <section class="header">
        <section class="title">
            <h2>Utlägg för <?= $title ?></h2>
        </section>
        <section class="for-cashier">
            <div class="note">
                Fylls i av kassör:
            </div>
            <div class="expense-report-input"><div class="expense-report-label">Verifikation:</div><div class="expense-report-line"></div></div>
            <div class="expense-report-input"><div class="expense-report-label">Signatur:</div><div class="expense-report-line"></div></div>
        </section>
    </section>
    <section class="body">
        <section class="inputs">
            <section class="inputs-qr">
                <p>Scanna QR-koden med din mobil för att fylla i detaljerna digitalt. Då behöver du bara fästa kvittot och lämna in blanketten med fälten nedan tomma.</p>
                <div class="qr-code-container">
                    <img class="qr-code" src="" data-qr-value="<?= htmlentities( $row['form_link'] )?>">
                </div>
            </section>
            <section class="inputs-what">
                <h3>Om utlägget:</h3>
                <div class="expense-report-input"><div class="expense-report-label">Id:</div><div class="expense-report-line"><?= $row['key'] ?></div></div>
                <div class="expense-report-input"><div class="expense-report-label">Beskrivning:</div><div class="expense-report-line"></div></div>
                <div class="expense-report-input"><div class="expense-report-label">Belopp:</div><div class="expense-report-line"></div></div>
                <div class="expense-report-input"><div class="expense-report-label">Datum:</div><div class="expense-report-line"></div></div>
            </section>
            <section class="inputs-who">
                <h3>Om dig:</h3>
                <div class="expense-report-input"><div class="expense-report-label">Namn:</div><div class="expense-report-line"></div></div>
                <div class="expense-report-input"><div class="expense-report-label">Epostadress:</div><div class="expense-report-line"></div></div>
                <div class="expense-report-input"><div class="expense-report-label">Bankkonto:</div><div class="expense-report-line"></div></div>
            </section>
        </section>
        <section class="expense-report-receipt-placeholder">
            <p>
                Fäst kvitto här.
            </p>
            <p class="expense-report-receipt-note note">
                Om kvittot omfattar sånt som du inte ska ha ersättning för så
                stryker du över eller ringar in så att det är tydligt vad du vill ha ersättning för.
            </p>
        </section>
    </section>
</article>
<?php } ?>
