<?php
/**
 * Template do formulário de agendamento do Colégio Positivo.
 *
 * O conteúdo deste arquivo é o HTML fornecido pelo usuário.
 * O CSS e o JS serão tratados separadamente ou incluídos aqui, se for o caso.
 */

// O HTML fornecido é um arquivo HTML completo, incluindo <html>, <head>, e <body>.
// Para ser usado dentro de um shortcode, apenas o conteúdo relevante (o corpo do formulário)
// deve ser mantido, e o CSS deve ser extraído ou enfileirado.

// O HTML fornecido tem 1320 linhas e inclui CSS inline na tag <style>.
// Para o WordPress, o ideal é:
// 1. Extrair o CSS para um arquivo separado e enfileirar.
// 2. Usar apenas o corpo do HTML (o que está dentro da tag <body>).

// Por simplificação e para manter a fidelidade ao arquivo original, vou extrair
// o CSS e o HTML do corpo e colocar no template.

// --- Conteúdo do CSS (Linhas 8 a 500 do index(1).html) ---
$css_content = '
  :root {
    --brand-orange: #ef6c00;
    --brand-orange-dark: #c45400;
    --text-dark: #1f1f1f;
    --text-mid: #4a4a4a;
    --border-gray: #d9d9d9;
    --bg-page: #ffffff;
    --bg-section: #fafafa;
    --radius-card: 12px;
    --radius-md: 6px;
    --radius-full: 999px;
    --shadow-card: 0 24px 48px rgba(0,0,0,0.06);
    --shadow-modal: 0 24px 48px rgba(0,0,0,0.25);
    --font-main: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  }

  /* --- Estilos adicionais para seleção de alunos --- */
  #studentsBox.hidden { display: none; }
  #studentsBox .card-like {
    background-color: #fff;
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    border: 1px solid rgba(0,0,0,0.03);
    padding: 16px;
    margin-bottom: 16px;
  }
  #studentsBox h3 {
    margin-top: 0;
    margin-bottom: 8px;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
  }
  #studentsBox .students-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
  }
  #studentsBox .student-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border: 1px solid var(--border-gray);
    border-radius: var(--radius-md);
    background-color: var(--bg-page);
    cursor: pointer;
  }
  #studentsBox .student-item:hover {
    background-color: #f7f7f7;
  }
  #studentsBox .student-item button {
    margin-left: 12px;
  }
  * {
    box-sizing: border-box;
    -webkit-font-smoothing: antialiased;
  }

  body {
    margin: 0;
    background-color: var(--bg-section);
    font-family: var(--font-main);
    color: var(--text-dark);
    line-height: 1.4;
  }

  /* ================= HEADER ================= */
  header.site-header {
    background-color: var(--brand-orange);
    color: #fff;
    padding: 12px 16px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
  }

  .header-inner {
    width: 100%;
    max-width: 1280px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .brand-block {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
  }

  .brand-logo {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background-color: #fff;
    color: var(--brand-orange);
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
  }

  nav.main-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
    font-weight: 500;
  }

  nav.main-nav a {
    color: #fff;
    text-decoration: block;
    display: flex;
    align-items: center;
    gap: 4px;
    line-height: 1.2;
    opacity: 0.95;
  }

  nav.main-nav a:hover {
    text-decoration: underline;
    opacity: 1;
  }

  nav.main-nav .pill-area-aluno {
    border: 1px solid #fff;
    border-radius: 999px;
    padding: 6px 12px;
    line-height: 1.2;
    font-weight: 600;
  }

  /* ================= HERO ================= */
  .hero {
    text-align: center;
    background-color: var(--bg-page);
    padding: 32px 16px 16px;
  }

  .hero-inner {
    max-width: 960px;
    margin: 0 auto;
  }

  .hero h1 {
    margin: 0 0 12px;
    color: var(--brand-orange);
    font-size: clamp(1.4rem, 0.5vw + 1rem, 1.8rem);
    font-weight: 600;
    line-height: 1.3;
  }

  .hero p {
    margin: 0;
    color: var(--text-mid);
    font-size: 15px;
    line-height: 1.5;
  }

  /* ================= LAYOUT PRINCIPAL ================= */
  .page-wrapper {
    max-width: 1280px;
    margin: 24px auto 80px;
    padding: 0 16px 64px;
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
  }

  /* Estado bloqueado */
  .page-wrapper :is(
    input:disabled,
    select:disabled,
    textarea:disabled,
    button:disabled
  ) {
    background-color: #f3f3f3 !important;
    color: #9a9a9a !important;
    border-color: #d6d6d6 !important;
    cursor: not-allowed;
  }

  .page-wrapper.is-disabled {
    opacity: 0.45;
    filter: grayscale(1);
  }



  /* CARD FORM PRINCIPAL */
  .form-card {
    background-color: #fff;
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    border: 1px solid rgba(0,0,0,0.03);
    padding: 24px 24px 32px;
  }

  /* Barra "Nossos Colégios" (cidade / unidade) */
  .top-selects {
    display: none;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
  }

  .top-selects .select-block {
    flex: 1 1 240px;
    position: relative;
  }

  .select-block label {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-mid);
    display: block;
    margin-bottom: 6px;
  }

  select.city-unit {
    width: 100%;
    border-radius: 8px;
    border: 1px solid var(--border-gray);
    background-color: #fff;
    font-size: 15px;
    line-height: 1.4;
    padding: 12px 14px;
    appearance: none;
    background-image:
      linear-gradient(45deg, transparent 50%, #777 50%),
      linear-gradient(135deg, #777 50%, transparent 50%);
    background-position:
      calc(100% - 18px) calc(50% - 3px),
      calc(100% - 13px) calc(50% - 3px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
    color: #2b2b2b;
  }

  select.city-unit:focus {
    outline: none;
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px rgba(239,108,0,0.25);
  }

  /* ================= STEPS HEADER ================= */
  .steps-header {
    border-top: unset;
    border-bottom: 1px solid var(--border-gray);
    padding: 16px 0;
    margin-bottom: 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    font-size: 14px;
  }

  .step-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #777;
    font-weight: 500;
    line-height: 1.2;
  }

  .step-chip .num {
    min-width: 20px;
    height: 20px;
    border-radius: var(--radius-full);
    border: 2px solid currentColor;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    line-height: 1;
  }

  .step-chip.active {
    color: var(--brand-orange);
    font-weight: 600;
  }

  /* =============== FORMS (STEPS) =============== */
  form {
    width: 100%;
  }

  .step-view:not(.active-step) {
    display: none;
  }

  .form-group {
    margin-bottom: 16px;
  }

  .form-row-2 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }
  @media(min-width:600px){
    .form-row-2 {
      grid-template-columns: 1fr 1fr;
    }
  }

  .label-line {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-mid);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .label-line .required {
    color: var(--brand-orange);
    font-weight: 600;
  }

  input[type="text"],
  input[type="email"],
  input[type="tel"],
  input[type="date"],
  select.data-select {
    width: 100%;
    border-radius: 8px;
    border: 1px solid var(--border-gray);
    background-color: #fff;
    padding: 12px 14px;
    font-size: 15px;
    line-height: 1.4;
    color: #2b2b2b;
    outline: none;
    transition: all .15s;
    font-family: inherit;
  }

  input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
  }

  input[type="date"].date-active {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px rgba(239,108,0,0.15);
  }

  input[type="number"] {
    width: 100%;
    border-radius: 8px;
    border: 1px solid var(--border-gray);
    background-color: #fff;
    padding: 12px 14px;
    font-size: 15px;
    line-height: 1.4;
    color: #2b2b2b;
    outline: none;
    transition: all .15s;
    font-family: inherit;
  }
  input[type="number"]:focus {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px rgba(239,108,0,0.25);
  }

  /* ====== ESTILO DOS HORÁRIOS ====== */
  .horarios-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  /* HORÁRIO - NÃO SELECIONADO */
  .time-slot {
      background-color: white !important;
      color: #F2A66A !important;
      border-radius: 8px !important;
      padding: 10px 16px !important;
      font-weight: 600 !important;
      cursor: pointer !important;
      border-color: #F2A66A !important;
      box-shadow: none !important;
      transition: all 0.2s ease !important;
  }

  /* HOVER */
  .time-slot:hover {
      opacity: 0.9 !important;
  }

  /* HORÁRIO SELECIONADO */
  .time-slot.active {
      background-color: #E56A00 !important;
      color: #ffffff !important;
      box-shadow: 0 0 0 2px rgba(229, 106, 0, 0.35) !important;
  }

  select.data-select,
  .serie-select {
    appearance: none;
    background-image:
      linear-gradient(45deg, transparent 50%, #777 50%),
      linear-gradient(135deg, #777 50%, transparent 50%);
    background-position:
      calc(100% - 18px) calc(50% - 3px),
      calc(100% - 13px) calc(50% - 3px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
  }

  /* ===== MODAL FULLSCREEN DE SUCESSO ===== */
  .modal-sucesso {
      position: fixed;
      top:0; left:0; right:0; bottom:0;
      background: rgba(0,0,0,0.55);
      z-index: 999999;
      justify-content: center;
      align-items: center;
  }

  .modal-sucesso .modal-content {
      background: #fff;
      width: 90%;
      max-width: 480px;
      padding: 24px 40px;
      border-radius: 18px;
      text-align: center;
      font-family: Inter, sans-serif;
      overflow: auto;
      height: 95%;
      place-content: center;
  }

  .modal-sucesso .icon-success {
      font-size: 26px;
      color: #4CAF50;
      margin-bottom: 20px;
  }

  .modal-sucesso h2 {
      font-size: 18px;
      margin-bottom: 10px;
      font-weight: 700;
  }

  .modal-sucesso .sub {
      color: #444;
      margin-bottom: 15px;
  }

  .modal-sucesso .box-info {
      background: #fff7ed;
      border: 2px solid #ffb878;
      border-radius: 12px;
      padding: 20px;
      text-align: left;
      margin-bottom: 15px;
  }

  .modal-sucesso h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 700;
      color: #333;
      margin-bottom: 6px;
  }

  .modal-sucesso p {
      margin: 3px 0;
      font-size: 15px;
  }

  .modal-sucesso .btn-fechar {
      background: #e87a1c;
      border: none;
      border-radius: 10px;
      color: #fff;
      padding: 12px 28px;
      font-size: 16px;
      cursor: pointer;
  }

  .modal-sucesso .btn-fechar:hover {
      background: #d36a0f;
  }


  @keyframes popIn {
    from { transform: scale(.85); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
  }


  input:focus,
  select.data-select:focus,
  .serie-select:focus {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px rgba(239,108,0,0.25);
  }

  .radio-line {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 15px;
    color: var(--text-mid);
  }

  /* Lista de alunos já cadastrados */
  .alunos-lista {
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    background-color: #fff;
    padding: 16px;
    margin-bottom: 16px;
  }

  .aluno-item {
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    line-height: 1.4;
    color: #2b2b2b;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
  }

  .aluno-info strong {
    display: block;
    font-size: 15px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 4px;
  }

  .aluno-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  /* FOOTER ACTIONS genéricas (passos 1-3) */
  .footer-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
    border-top: 1px solid var(--border-gray);
    padding-top: 24px;
    justify-content: flex-end;
  }

  button.btn {
    appearance: none;
    border: 0;
    border-radius: 8px;
    background-color: var(--brand-orange);
    color: #fff;
    font-weight: 600;
    font-size: 15px;
    line-height: 1.2;
    padding: 12px 16px;
    cursor: pointer;
    font-family: inherit;
    box-shadow: 0 8px 20px rgba(239,108,0,0.3);
    transition: background-color .15s, box-shadow .15s;
  }
  button.btn:hover {
    background-color: var(--brand-orange-dark);
    box-shadow: 0 8px 20px rgba(196,84,0,0.4);
  }

  button.btn.secondary {
    background-color: #fff;
    color: var(--brand-orange);
    border: 2px solid var(--brand-orange);
    box-shadow: none;
  }
  button.btn.secondary:hover {
    background-color: rgba(239,108,0,0.08);
    box-shadow: none;
  }

  button.btn.prev {
    background-color: #fff;
    border: 2px solid #777;
    color: #444;
    box-shadow: none;
  }
  button.btn.prev:hover {
    background-color: #f2f2f2;
  }

  .small-note {
    color: #666;
    font-size: 13px;
    line-height: 1.4;
  }

  /* ================= SIDE CARD (UNIDADE) ================= */
  .school-card {
    background-color: #fff;
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    border: 1px solid rgba(0,0,0,0.03);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .school-img {
    width: 100%;
    height: 180px;
    background-image: url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1200&q=60");
    background-size: cover;
    background-position: center;
  }

  .school-info {
    padding: 20px 24px 24px;
    font-size: 14px;
    line-height: 1.5;
    color: var(--text-mid);
  }

  .school-nome {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 4px;
  }

  .school-etapas {
    display: inline-block;
    color: var(--brand-orange);
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 12px;
    line-height: 1.4;
  }

  .school-info-row {
    margin-bottom: 6px;
  }

  /* ================= MODAL SUCESSO ================= */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.4);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    z-index: 9999;
  }

  .modal-card {
    background: #fff;
    max-width: 420px;
    width: 100%;
    border-radius: 16px;
    box-shadow: var(--shadow-modal);
    position: relative;
    text-align: center;
    padding: 32px 24px 40px;
  }
  
  .modal-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background-color: var(--brand-orange);
    color: #fff;
    font-size: 32px;
  }
  
  .modal-card h2 {
    margin: 0 0 12px;
    font-size: 20px;
    font-weight: 600;
    color: var(--text-dark);
    line-height: 1.3;
  }
  
  .modal-card p {
    margin: 0 0 24px;
    font-size: 15px;
    color: var(--text-mid);
    line-height: 1.5;
  }
  
  .modal-card button.btn {
    width: 100%;
  }

  .modal-sucesso-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 99999;
      padding: 20px;
  }

  .modal-sucesso-overlay.hidden {
      display: none !important;
  }

  .modal-sucesso-card {
      background: #fff;
      width: 100%;
      max-width: 650px;
      border-radius: 20px;
      padding: 40px 32px;
      text-align: center;
      box-shadow: 0 20px 45px rgba(0,0,0,0.15);
  }

  .modal-sucesso-icone {
      width: 78px;
      height: 78px;
      background: #4CAF50;
      color: #fff;
      font-size: 45px;
      border-radius: 999px;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0 auto 20px;
  }

  .modal-sucesso-titulo {
      font-size: 26px;
      font-weight: 700;
      color: #333;
      margin-bottom: 14px;
      line-height: 1.3;
  }

  .modal-sucesso-sub {
      font-size: 16px;
      color: #666;
      margin-bottom: 25px;
  }

  .modal-sucesso-box {
      background: #FFF7EC;
      border: 1px solid #EAB574;
      border-radius: 12px;
      padding: 20px 24px;
      text-align: left;
      font-size: 15px;
      line-height: 1.5;
      margin-bottom: 32px;
  }

  .modal-sucesso-box p {
      margin: 0 0 12px;
  }

  .modal-sucesso-box strong {
      color: #333;
  }

  .modal-sucesso-btn {
      background: #e88a2d;
      color: #fff;
      font-size: 17px;
      font-weight: 600;
      border: none;
      padding: 14px 40px;
      border-radius: 10px;
      cursor: pointer;
      transition: .2s;
  }

  .modal-sucesso-btn:hover {
      background: #cc741c;
  }


  /* ================= FOOTER ================= */
  footer.site-footer {
    background-color: #2b2b2b;
    color: #fff;
    padding: 24px 16px;
    text-align: center;
    font-size: 13px;
  }
  
  footer.site-footer p {
    margin: 0;
    opacity: 0.8;
  }
  
  /* ================= UTILITÁRIOS ================= */
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .hidden { display: none !important; }
  .mt-0 { margin-top: 0 !important; }
  .mb-0 { margin-bottom: 0 !important; }
  .mb-16 { margin-bottom: 16px !important; }
  .mb-24 { margin-bottom: 24px !important; }
  .mb-32 { margin-bottom: 32px !important; }
  .mt-24 { margin-top: 24px !important; }
  .mt-32 { margin-top: 32px !important; }
  .p-0 { padding: 0 !important; }
  
  /* Icones (simulação) */
  .icon-map, .icon-phone, .icon-mail, .icon-check, .icon-user {
    font-family: "Font Awesome 5 Free"; /* Simulação de ícones */
    font-weight: 900;
    font-style: normal;
    display: inline-block;
    line-height: 1;
  }

  /* ================= LOADING (Busca de Aluno) ================= */
  .loading-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border: 1px solid #eee;
    border-radius: 8px;
    background-color: #fff;
  }
  .loading-box .spinner {
    width: 20px;
    height: 20px;
    border: 3px solid #ddd;
    border-top-color: var(--brand-orange);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
  .loading-box--dates {
    margin-top: 8px;
    padding: 18px 20px;
    border-color: rgba(239, 108, 0, 0.22);
    background: linear-gradient(135deg, #fff7f0 0%, #fff1e3 100%);
    box-shadow: 0 10px 24px rgba(239, 108, 0, 0.08);
  }
  .loading-box--dates p {
    margin: 0;
    color: #c45400;
    font-size: 15px;
    font-weight: 700;
  }
  .loading-box--dates .spinner {
    width: 22px;
    height: 22px;
    border-width: 3px;
    border-color: #ffd4b0;
    border-top-color: var(--brand-orange);
    flex-shrink: 0;
  }
  .agenda-status-box {
    margin-top: 8px;
    padding: 18px 20px;
    border: 1px solid rgba(239, 108, 0, 0.18);
    border-radius: 10px;
    background: #fff8f3;
    color: #9f4700;
  }
  .agenda-status-box p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
  }
  .agenda-status-box .btn {
    margin-top: 12px;
  }
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  .icon-map::before { content: "\f3c5"; } /* fa-map-marker-alt */
  .icon-phone::before { content: "\f095"; } /* fa-phone */
  .icon-mail::before { content: "\f0e0"; } /* fa-envelope */
  .icon-check::before { content: "\f00c"; } /* fa-check */
  .icon-user::before { content: "\f007"; } /* fa-user */

  #agendamento-loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(4px);
      z-index: 999999;
      display: flex;
      align-items: center;
      justify-content: center;
  }

  #agendamento-loading-overlay .loader-box {
      text-align: center;
      font-family: "Inter", sans-serif;
  }

  #agendamento-loading-overlay p {
      margin-top: 18px;
      margin-left: 4px;
      font-size: 22px;
      font-weight: 600;
      color: #d66100;
  }

  .spinner {
      width: 35px;
      height: 35px;
      border: 6px solid #ffb06b;
      border-top-color: #d66100;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
      to { transform: rotate(360deg); }
  }

  .form-blocked {
    opacity: 0.4;
    pointer-events: none;
  }

  .agenda-dia {
    margin-bottom: 18px;
  }

  .agenda-dia-label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #444;
  }

  /* =========================================================
    SELECT2 – AJUSTE VISUAL PARA IGUALAR INPUT DO FORM
  ========================================================= */

  .select2-container {
    width: 100% !important;
  }

  .select2-container--default .select2-selection--single {
    height: 48px !important; /* igual ao input */
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    padding: 0 12px;
    display: flex !important;
    align-items: center !important;
    font-size: 16px;
  }
  .select2-container .select2-selection--single {
    height: 48px !important;
    display: flex !important;
    align-items: center !important;
  }

  .select2-selection__rendered {
    line-height: normal !important;
  }

  .select2-selection__arrow {
    height: 48px !important;
  }

  .select2-container--default .select2-selection--single
  .select2-selection__rendered {
    line-height: normal;
    padding: 0;
    color: #333;
  }

  .select2-container--default .select2-selection--single
  .select2-selection__placeholder {
    color: #999;
  }

  .select2-container--default .select2-selection--single
  .select2-selection__arrow {
    height: 100%;
    right: 10px;
  }

  /* Foco (igual input) */
  .select2-container--default.select2-container--focus
  .select2-selection--single {
    border-color: #f58220; /* laranja Positivo */
    box-shadow: 0 0 0 1px rgba(245,130,32,.3);
  }

  .select2-container--open .select2-search__field {
    color: #2b2b2b !important;
    caret-color: #2b2b2b;
    font-family: var(--font-main);
  }

  .select2-results__option,
  .select2-selection__rendered{
    font-weight: initial !important;
    font-family: var(--font-main);
  }

  .select2-container--default .select2-selection--single .select2-selection__clear{
    display: none;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered{
    max-width: 500px;
  }

  /* Dropdown */
  .select2-dropdown {
    border-radius: 6px;
    border-color: #d0d0d0;
  }

  .select2-container--default .select2-results__option--disabled{
    color:#ef6c00 !important;
  }

  .btn-remove-aluno {
    border-radius: 8px !important;
    padding: 8px 14px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
  }

  .btn-remove-aluno:hover {
    background: #fdecea;
  }

  .aluno-fields label{
    margin-bottom:10px;
  }


';

// --- Conteúdo do HTML (Linhas 505 a 1319 do index(1).html - conteúdo do body) ---
$html_body = '

<main>
  <div class="page-wrapper">
    <div class="form-card">
      <div class="top-selects">
        <div class="select-block">
          <label for="city-select">Cidade</label>
          <select id="city-select" class="city-unit" name="city" disabled>
            <!--
              As opções de cidade serão preenchidas dinamicamente via JavaScript
              após a consulta das unidades na API. No carregamento inicial,
              mostramos um placeholder indicando que os dados estão sendo
              carregados. Este seletor será habilitado quando as cidades
              estiverem disponíveis.
            -->
            <option value="">Carregando...</option>
          </select>
        </div>
        <div class="select-block">
          <label for="unit-select">Unidade</label>
          <select id="unit-select" class="city-unit" name="unit" disabled>
            <!--
              As unidades dependem da cidade selecionada. Após o usuário
              escolher uma cidade, o JavaScript irá popular este select
              com as unidades correspondentes. Inicialmente está desabilitado.
            -->
            <option value="">Selecione a unidade</option>
          </select>

          <!-- Campo oculto para armazenar o ID do colégio (cad_categoriaid) da unidade selecionada -->
        </div>
      </div>

      <div class="steps-header">
        <div class="step-chip active" data-step="1">
          <span class="num">1</span> Dados do Responsável
        </div>
        <div class="step-chip" data-step="2">
          <span class="num">2</span> Busca de Aluno
        </div>
        <div class="step-chip" data-step="3">
          <span class="num">3</span> Dados do Aluno
        </div>
        <div class="step-chip" data-step="4">
          <span class="num">4</span> Agendamento
        </div>
        <div class="step-chip" data-step="5">
          <span class="num">5</span> Confirmação
        </div>
      </div>

      <form id="agendamento-form">
        <input type="hidden" id="cadCategoriaId" name="crm_unidadeinteresse" value="" />
        <input type="hidden" id="unidade_nome" name="unidade_nome" value="" />
        <input type="hidden" name="utm_source">
        <input type="hidden" name="utm_medium">
        <input type="hidden" name="utm_campaign">
        <input type="hidden" name="utm_term">
        <input type="hidden" name="utm_content">
        <input type="hidden" name="servico">
        <input type="hidden" name="recurso">

        <!-- PASSO 1: DADOS DO RESPONSÁVEL -->
        <div class="step-view active-step" data-step="1">
          <h2 class="mb-24">Dados do Responsável</h2>
          <div class="form-group">
            <label for="responsavel_nome" class="label-line">Nome do Responsável <span class="required">*</span></label>
            <input type="text" id="responsavel_nome" name="responsavel_nome" placeholder="Informe o nome do Responsável" required />
          </div>
          <div class="form-group">
            <label for="responsavel_telefone" class="label-line">Telefone <span class="required">*</span></label>
            <input type="tel" id="responsavel_telefone" name="responsavel_telefone" placeholder="(41) 99999-0000" required />
          </div>
          <div class="form-group">
            <label for="responsavel_email" class="label-line">E-mail do responsável <span class="required">*</span></label>
            <input type="email" id="responsavel_email" name="responsavel_email" placeholder="Informe seu melhor e-mail" required />
          </div>
          <!-- <div class="form-group">
            <label for="responsavel_serie_id" class="label-line">Série de Interesse <span class="required">*</span></label>
            <select id="responsavel_serie_id" name="responsavel_serie_id" class="serie-select data-select" required>
              <option value="">Carregando...</option>
            </select>
            <input type="hidden" id="responsavel_serie" name="responsavel_serie" />
          </div> -->
          <div class="footer-actions">
            <button type="button" class="btn next-step search-responsavel" data-next-step="2">Informar dados do Aluno</button>
          </div>
        </div>

        <!-- PASSO 2: BUSCA DE ALUNO (loading) -->
        <div class="step-view" data-step="2">
          <h2 class="mb-24">Busca de Aluno</h2>
          <div id="buscaAlunoLoading" class="loading-box">
            <div class="spinner"></div>
            <p>Buscando alunos do responsável…</p>
          </div>
        </div>

        <!-- PASSO 3: DADOS DO ALUNO -->
        <div class="step-view" data-step="3">
          <h2 class="mb-24">Dados do Aluno</h2>
          <!-- Caixa de seleção de alunos encontrados -->
          <div id="studentsBox" class="hidden">
            <div class="card-like">
              <h3>Alunos encontrados</h3>
              <p id="studentsIntro" class="muted"></p>
              <div id="studentsList" class="students-list"></div>
              <div class="button-group" style="display:flex; gap:12px; flex-wrap:wrap;">
                <!-- Botão para cadastrar um novo aluno -->
                <button type="button" class="btn secondary edit-dados">Cadastrar novo aluno</button>
              </div>
            </div>
          </div>
          <!-- Campo oculto para armazenar o ID do aluno selecionado -->
          <input type="hidden" id="selected_student_id" name="selected_student_id" value="" />
          <!-- Seção de cadastro manual de alunos -->
          <div class="step-3-manual">
            <div class="aluno-fields">
              <div class="form-group">
                <label>Nome completo do Aluno <span class="required">*</span></label>
                <input type="text" name="aluno_nome[]" required />
              </div>
              <div class="form-group escola-origem-group">
                <label>Selecione a Escola de Origem <span class="required">*</span></label>
                <select
                  class="escola-select"
                  name="aluno_escola[]"
                  data-placeholder="Digite o nome da escola"
                  style="width:100%">
                    <option></option>
                    <option value="primeira_escola">Primeira Escola</option>
                </select>
              </div>
              <div class="form-group form-row-2-small" style="display:flex; gap:16px; flex-wrap:wrap;">
                <div style="flex:1 1 120px;">
                  <label>Ano de Matrícula <span class="required">*</span></label>
                  <select name="aluno_ano[]" class="ano-matricula-select data-select" required></select>
                </div>
                <div style="flex:1 1 120px;">
                  <label>Qual a série desejada? <span class="required">*</span></label>
                  <select name="aluno_serie_id[]" class="serie-select data-select" required>
                    <option value="">Carregando...</option>
                  </select>
                  <input type="hidden" name="aluno_serie[]" class="serie-name" />
                </div>
              </div>
            </div>
            <button type="button" class="btn secondary add-aluno" style="margin-top:12px;">Adicionar aluno</button>
            <div class="footer-actions" style="margin-top:24px;">
              <button type="button" class="btn prev" data-prev-step="1">Voltar</button>
              <button type="button" class="btn next-step" data-next-step="4">Selecionar data da visita</button>
            </div>
          </div>
        </div>

        <!-- PASSO 4: AGENDAMENTO -->
        <div class="step-view" data-step="4">
          <h2 class="mb-24">Agendamento</h2>

          <p class="mb-16">
            Selecione um <strong>dia</strong> e um <strong>horário disponível</strong> para sua visita:
          </p>

          <!-- Campos ocultos que serão preenchidos via JS -->
          <input type="hidden" id="selected_date" name="agendamento_data" required>
          <input type="hidden" id="selected_time" name="agendamento_hora" required>

          <!-- Container dos próximos dias disponíveis -->
          <div id="agendaDias" class="agenda-dias">
            <!-- Preenchido via AJAX:
                positivo_crm_get_next_available_dates -->
          </div>

          <div class="form-group" style="margin-top:16px;">
            <label class="terms">
              <input type="checkbox" id="acceptTerms" required>
              Eu li e aceito os termos de
              <a href="https://colegiopositivo.com.br/politica-de-privacidade" target="_blank">
                Política de Privacidade
              </a>
              do Colégio Positivo
            </label>
          </div>

          <div class="footer-actions" style="margin-top:24px;">
            <button type="button" class="btn prev" data-prev-step="3">Voltar</button>
            <button type="submit" class="btn" id="submitAgendamento">
              Realizar agendamento
            </button>
          </div>
        </div>


        <!-- PASSO 5: CONFIRMAÇÃO (Será exibido via JS após o submit) -->
      </form>
    </div>
  </div>
</main>


<div id="agendamento-loading-overlay" class="loading-overlay hidden">
    <div class="spinner"></div>
    <p>Enviando agendamento...</p>
</div>

<div id="agendamentoSucessoModal" class="modal-sucesso" style="display:none;">
    <div class="modal-content">
        <div class="icon-success">✔</div>

        <h2>Agendamento realizado<br>com sucesso!</h2>
        <div class="box-info">

            <h3>Responsável:</h3>
            <p><span class="resp-nome"></span></p>
            <p>Tel.: <span class="resp-tel"></span></p>
            <p>E-mail: <span class="resp-email"></span></p>

            <br>

            <h3>Aluno(s):</h3>
            <p><span class="aluno-nome"></span></p>
            <p>Série: <span class="aluno-serie"></span></p>
            <p>Ano: <span class="aluno-ano"></span></p>
            <p>Escola: <span class="aluno-escola"></span></p>

            <br>

            <h3>Detalhes da Visita:</h3>
            <p>Unidade: <span class="visita-unidade"></span></p>
            <p>Data: <span class="visita-data"></span></p>
            <p>Horário: <span class="visita-hora"></span></p>
        </div>

        <button class="btn-fechar" onclick="location.reload()">Fechar</button>
    </div>
</div>

';

// Enfileira o CSS como um bloco de estilo inline, pois o HTML original usa variáveis CSS e é um bloco grande.
// Para um plugin WordPress, o ideal é enfileirar um arquivo CSS.
// Vamos criar o arquivo CSS e enfileirar na fase 5. Por enquanto, injetamos o CSS no template.
// NOTA: O CSS original usa `body` e `html` que podem interferir no tema do WordPress.
// Para minimizar a interferência, vou remover as tags `body` e `html` do HTML e tentar encapsular o CSS.
// No entanto, o CSS é muito extenso e usa seletores globais. Vou criar o arquivo CSS e enfileirar.

// Preparar assets inline em vez de gravá-los em disco. A partir desta versão,
// as folhas de estilo e scripts são incorporados diretamente na saída do
// shortcode para evitar problemas de acesso a arquivos por usuários não
// autenticados.

// Emite o CSS em uma tag <style>

echo '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>';
echo '<style type="text/css">' . $css_content . '</style>';

// O HTML do body é o que realmente será renderizado pelo shortcode.
echo '<div class="positivo-agendamento-wrapper">' . $html_body . '</div>';

// Inclui o CSS e JS diretamente na página para garantir que funcionem mesmo
// quando o servidor impedir o acesso direto aos arquivos.
$ajax_url = admin_url('admin-ajax.php');
$ajax_nonce = wp_create_nonce('positivo-crm-nonce');

// Emite as variáveis globais e o script principal
echo '<script type="text/javascript">';
echo 'var PositivoCRM = {
    "ajax_url": "' . esc_js($ajax_url) . '",
    "nonce": "' . esc_js($ajax_nonce) . '"
};';
echo '</script>';

echo '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
// Agora o JS principal em um <script> separado
echo '<script type="text/javascript">';
echo <<<'JAVASCRIPT'
(function($) {
  $(document).ready(function() {
      function getUTMParams() {
        const params = new URLSearchParams(window.location.search);
        const utms = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content'
        ];
        utms.forEach(function(param) {
            const value = params.get(param);

            if (value) {
                const input = document.querySelector(`input[name="${param}"]`);
                if (input) {
                    input.value = value;
                }
            }
        });
      }
      getUTMParams();

      const $form = $("#agendamento-form");
      const $stepsHeader = $(".steps-header");
      const $steps = $form.find(".step-view");
      const $citySelect = $("#city-select");
      const $unitSelect = $("#unit-select");
      const $cadCategoria = $("#cadCategoriaId");
      const $successModal = $("#success-modal");
      let currentStep = 1;

    // ========================== MÁSCARAS ==========================
    $("input[type='tel']").on("input", function () {
        let v = $(this).val().replace(/\D/g, "");

        // Limita a 11 dígitos
        if (v.length > 11) v = v.slice(0, 11);

        let f = "";

        if (v.length > 0) f = "(" + v.substring(0, 2);
        if (v.length >= 3) f += ") " + v.substring(2, 7);
        if (v.length >= 8) f += "-" + v.substring(7);

        $(this).val(f);
    });


    $("input[type='date']").on("focus", function() {
      $(this).addClass("date-active");
    }).on("blur", function() {
      $(this).removeClass("date-active");
    });

    // ======================= FUNÇÕES ==========================
    function extractCity(addr) {
      const s = (addr || "").toString().trim();
      const m = s.match(/,\s*([^,-]+?)\s*-\s*[A-Za-z]{2}\s*$/);

      if (m && m.length > 1) {
        return m[1].trim();
      }

      const parts = s.split(",");
      if (parts.length > 1) {
        return parts[parts.length - 1]
          .replace(/\s*-\s*[A-Za-z]{2}\s*$/, "")
          .trim();
      }

      return s;
    }


    const unitsByCity = {};

    function getAlunoId(a) {
      return a.leadid || a.lead_id || a.studentid || a.aluno_id || a.msdyn_leadid || a.id || a.msdyn_id || null;
    }
    function getAlunoNome(a) {
      return a.fullname || a.nome || a.aluno_nome || "";
    }
    function getAlunoSerie(a) {
      return a.aluno_serie || a.serie || a.col_turnointeresse || "";
    }
    function getAlunoAno(a) {
      return a.aluno_ano || a.ano || a.col_anointeresse || "";
    }
    function getAlunoEscola(a) {
      return a.escola || a.aluno_escola || a.school || a.cad_inscricaoatual || "";
    }
    function getAlunoNascimento(a) {
      const raw = a.cad_datanascimento || a.aluno_nascimento || a.nascimento || "";
      if (!raw) return "";
      try {
        const d = new Date(raw);
        if (!isNaN(d)) return d.toISOString().slice(0, 10);
      } catch(e){}
      return raw.substring(0,10);
    }


    // ===================== Parametro de Unidade =====================
    $('#unit-select').off('change').on('change', function () {
        const $opt = $(this).find('option:selected');

        const unidadeId   = $(this).val() || '';
        const unidadeNome = $opt.text().trim() || '';

        // 🔑 fonte única
        $('#cadCategoriaId').val(unidadeId);

        // guarda o nome como data-attribute (não precisa input hidden)
        $('#cadCategoriaId').data('unidade-nome', unidadeNome);
    });


    // ===================== LISTA DE ALUNOS =====================

    function showStudentsList(alunos) {
      $("#studentsList").empty();
      $("#selected_student_id").val("");

      $("#studentsIntro").text("Selecione um aluno da lista ou cadastre um novo:");

      alunos.forEach(function(a) {
        const id = getAlunoId(a);
        const nome = getAlunoNome(a);
        const serie = getAlunoSerie(a);
        const ano = getAlunoAno(a);

        const line = $("<div>").addClass("student-item").attr("data-id", id);
        const txt = [];

        txt.push("<strong>" + nome + "</strong>");
        if (serie) txt.push("Série: " + serie);
        if (ano) txt.push("Ano: " + ano);

        const p = $("<p>").html(txt.join(" • "));
        const btn = $("<button>").attr("type", "button").addClass("btn secondary select-aluno").text("Selecionar");

        btn.on("click", function(e) {
          e.stopPropagation();
          handleStudentSelect(a);
        });

        line.on("click", function() {
          handleStudentSelect(a);
        });

        line.append(p).append(btn);
        $("#studentsList").append(line);
      });

      $("#studentsBox").removeClass("hidden");
      $(".step-3-manual").addClass("hidden");
      updateSteps(3);
    }

    function handleStudentSelect(aluno) {
      const id = getAlunoId(aluno);
      $("#selected_student_id").val(id || "");

      const $c = $(".step-3-manual .aluno-fields").first();

      $c.find("input[name='aluno_nome[]']").val(getAlunoNome(aluno));
      $c.find("input[name='aluno_escola[]']").val(getAlunoEscola(aluno));
      $c.find("input[name='aluno_ano[]']").val(getAlunoAno(aluno));
      $c.find("input[name='aluno_serie[]']").val(getAlunoSerie(aluno));

      $("#studentsBox").addClass("hidden");
      $(".step-3-manual").removeClass("hidden");

      $(".step-3-manual .aluno-fields").not($c).remove();

      updateSteps(3);
    }

    // ===================== NAVEGAÇÃO ENTRE ETAPAS =====================

    function updateSteps(step) {
      $stepsHeader.find(".step-chip").removeClass("active");
      $stepsHeader.find('.step-chip[data-step="' + step + '"]').addClass("active");

      $steps.removeClass("active-step");
      $form.find('.step-view[data-step="' + step + '"]').addClass("active-step");

      currentStep = step;
    }

    $form.on("click", ".next-step", function () {
      const next = parseInt($(this).data("next-step"));
      if (!validateStep(currentStep)) {
          alert("Por favor, preencha todos os campos obrigatórios.");
          return;
      }
      updateSteps(next);
      // 🔥 Ao entrar no passo 4, carrega agenda
      if (next === 4) {
          carregarProximosDias();
      }
    });


    $form.on("click", ".prev", function() {
      updateSteps(parseInt($(this).data("prev-step")));
    });

    $successModal.on("click", ".close-modal", function() {
      $successModal.addClass("hidden");
    });

    // ==================== CARREGAMENTO DE UNIDADES ====================

    loadUnits();

    $citySelect.on("change", function() {
      const city = $(this).val();
      $cadCategoria.val("");

      if (city && unitsByCity[city]) {
        let html = '<option value="">Selecione a unidade</option>';
        unitsByCity[city].forEach(u => {
          html += `<option value="${u.id}">${u.name}</option>`;
        });
        $unitSelect.html(html).prop("disabled", false);
      } else {
        $unitSelect.html('<option value="">Selecione a unidade</option>').prop("disabled", true);
      }
    });

    $unitSelect.on("change", function () {
      const unitSelected = $(this).val();
      const citySelected = $citySelect.val();
      // seta categoria (mantive seu comportamento)
      $cadCategoria.val(unitSelected);
      // validações básicas
      if (!citySelected || !unitSelected) {
        $('.serie-select')
          .html('<option value="">Selecione a série</option>')
          .prop('disabled', true);
        return;
      }
      const unidadesCidade = unitsByCity[citySelected];
      if (!Array.isArray(unidadesCidade)) {
        console.error('Cidade não encontrada em unitsByCity:', citySelected);
        return;
      }
      const unidade = unidadesCidade.find(u => u.id === unitSelected);
      if (!unidade || !Array.isArray(unidade.series)) {
        console.error('Unidade ou séries não encontradas:', unidade);
        $('.serie-select')
          .html('<option value="">Nenhuma série disponível</option>')
          .prop('disabled', true);
        return;
      }

      const series = unidade.series;
      const servico = unidade.servico;
      const recurso = unidade.recurso;
      // monta options
      let serieOptions = '<option value="">Selecione a série</option>';
      series.forEach(s => {
        const id = (s.id || '').replace(/[{}]/g, '');
        const name = s.nome || '';
        if (id && name) {
          serieOptions += `<option value="${id}">${name}</option>`;
        }
      });
      $('.serie-select').html(serieOptions).prop('disabled', false);
      document.querySelector('input[name="servico"]').value = servico;
      document.querySelector('input[name="recurso"]').value = recurso;
      // console.log('Séries carregadas:', series);
      // console.log('Servico carregada:', servico);
      // console.log('Recurso carregada:', recurso);
    });


    // ===================== BUSCA RESPONSÁVEL =====================

    $form.on("click", ".search-responsavel", function (e) {
      e.preventDefault();

      const nome = $("#responsavel_email").val().trim();
      if (!nome) {
          alert("Informe o nome do responsável.");
          return;
      }

      const $btn = $(this);
      $btn.prop("disabled", true).text("Buscando...");

      // Vai para STEP 2 e mantém o loading bloqueado até AJAX voltar
      $(".step-3-manual").addClass("hidden");
      $("#studentsBox").addClass("hidden");
      updateSteps(2);

      // MOSTRA LOADING
      $("#buscaAlunoLoading").show();

      $.post(PositivoCRM.ajax_url, {
          action: "positivo_crm_get_responsavel_e_alunos",
          nonce: PositivoCRM.nonce,
          fullname: nome
      })
      .done(function (resp) {

          // Sempre manter o loading até aqui
          $("#buscaAlunoLoading").hide();

          if (!resp.success || !resp.data) {
              $("#studentsBox").addClass("hidden");
              $(".step-3-manual").removeClass("hidden");
              updateSteps(3);
              return;
          }

          const responsavel = resp.data.responsavel || null;
          let alunos = resp.data.alunos || [];
          if (typeof alunos === "object" && alunos !== null && !Array.isArray(alunos)) {
              alunos = [alunos];
          }

          if (!responsavel) {
              $("#studentsBox").addClass("hidden");
              $(".step-3-manual").removeClass("hidden");
              updateSteps(3);
              return;
          }

          // Insere ID do responsável
          $("#responsavel_id_hidden").remove();
          $("<input>", {
              type: "hidden",
              id: "responsavel_id_hidden",
              name: "responsavel_id",
              value: responsavel.leadid
          }).appendTo($form);

          // Se tiver alunos
          if (alunos.length > 0) {
              showStudentsList(alunos);
          } else {
              $("#studentsBox").addClass("hidden");
              $(".step-3-manual").removeClass("hidden");
              updateSteps(3);
          }
      })
      .fail(function () {
          $("#buscaAlunoLoading").hide();
          alert("Erro de comunicação com o servidor.");
          $("#studentsBox").addClass("hidden");
          $(".step-3-manual").removeClass("hidden");
          updateSteps(3);
      })
      .always(function () {
          $btn.prop("disabled", false).text("Informar dados do Aluno");
      });
    });

    function debounce(fn, delay = 400) {
      let timer;
      return function (...args) {
          clearTimeout(timer);
          timer = setTimeout(() => {
              fn.apply(this, args);
          }, delay);
      };
    }
    /* ============================================================
      BUSCA DE ESCOLA DE ORIGEM (AUTOCOMPLETE)
    ============================================================ */
    const select2PtBr = {
      errorLoading: function () {
          return 'Os resultados não puderam ser carregados.';
      },
      inputTooLong: function (args) {
          const overChars = args.input.length - args.maximum;
          return 'Apague ' + overChars + ' caractere(s).';
      },
      inputTooShort: function (args) {
          const remainingChars = args.minimum - args.input.length;
          return 'Por gentileza, adicione ' + remainingChars + ' ou mais caractere(s).';
      },
      loadingMore: function () {
          return 'Carregando mais resultados…';
      },
      maximumSelected: function (args) {
          return 'Você só pode selecionar ' + args.maximum + ' item(ns).';
      },
      noResults: function () {
          return 'Nenhuma escola encontrada.';
      },
      searching: function () {
          return 'Buscando escolas…';
      },
      removeAllItems: function () {
          return 'Remover todos os itens';
      }
    };
    function initEscolaSelect(context = document) {
      $(context).find('.escola-select').each(function () {
        const $el = $(this);

        // ✅ Só destrói se o Select2 EXISTIR de verdade
        if ($el.data('select2')) {
            $el.select2('destroy');
        }

        $el.select2({
          placeholder: 'Digite o nome da escola',
          allowClear: true,
          minimumInputLength: 3,

          // 🔒 NÃO permite criar opção digitando
          tags: false,
          createTag: () => null,

          language: {
              inputTooShort: () => 'Digite pelo menos 3 caracteres',
              noResults: () => 'Nenhuma escola encontrada',
              searching: () => 'Buscando escolas...'
          },

          ajax: {
              url: PositivoCRM.ajax_url,
              type: 'POST',
              delay: 500,
              data: params => ({
                  action: 'positivo_crm_search_eschool_public',
                  nonce: PositivoCRM.nonce,
                  descricao: params.term
              }),
              processResults: resp => {
                  let results = resp?.data?.data?.map(s => ({
                      id: s.descricao,
                      text: s.descricao
                  })) || [];

                  // ➕ opções fixas (selecionáveis, mas NÃO digitáveis)
                  results.push(
                      {
                          id: 'nao_encontrei',
                          text: 'Não Encontrei minha Escola'
                      },
                      {
                          id: 'primeira_escola',
                          text: 'Primeira Escola'
                      }
                  );
                  return { results };
              }
          }
        });
        // 🔥 ao abrir o select, limpa o valor anterior
        $el.on('select2:opening', function () {
            $(this).val(null).trigger('change');
        });

        $el.on('select2:open', function () {
            const searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (!searchField) return;

            // Força o foco no campo de busca interno para o cursor aparecer já no primeiro clique.
            requestAnimationFrame(() => {
                searchField.focus();
                searchField.click();
            });
        });
      });
    }
    initEscolaSelect($('.aluno-fields').first());
    // $('.escola-select').select2({
    //   placeholder: 'Digite o nome da escola',
    //   allowClear: true,
    //   minimumInputLength: 3,
    //   ajax: {
    //       url: PositivoCRM.ajax_url,
    //       type: 'POST',
    //       delay: 500, // ⏱️ debounce NATIVO
    //       data: function (params) {
    //           return {
    //               action: 'positivo_crm_search_eschool_public',
    //               nonce: PositivoCRM.nonce,
    //               descricao: params.term
    //           };
    //       },
    //       processResults: function (resp) {

    //           if (!resp?.success || !resp?.data?.data) {
    //               return { results: [] };
    //           }

    //           return {
    //               results: resp.data.data.map(school => ({
    //                   id: school.descricao,
    //                   text: school.descricao
    //               }))
    //           };
    //       }
    //   },
    //   tags: true, // 🔥 permite digitar escola manual
    // });
    /* ============================================================
      ANO DE MATRÍCULA (DINÂMICO)
    ============================================================ */
    function preencherAnoMatricula($select) {
        const now = new Date();
        const anoAtual = now.getFullYear();
        const mesAtual = now.getMonth() + 1;

        let anoPrincipal = anoAtual;

        // Se estiver no fim do ano (outubro em diante), já projeta o próximo
        if (mesAtual >= 10) {
            anoPrincipal = anoAtual + 1;
        }

        const anos = [
            anoPrincipal,
            anoPrincipal + 1
        ];

        let html = "";
        anos.forEach((ano, index) => {
            html += `<option value="${ano}" ${index === 0 ? "selected" : ""}>${ano}</option>`;
        });

        $select.html(html);
    }
    // Inicialização
    $(".ano-matricula-select").each(function () {
        preencherAnoMatricula($(this));
    });
    // Quando adicionar novo aluno dinamicamente
    $form.on("click", ".add-aluno", function () {
        setTimeout(() => {
            $(".ano-matricula-select").each(function () {
                if (!$(this).children().length) {
                    preencherAnoMatricula($(this));
                }
            });
        }, 50);
    });
    /* ============================================================
      CARREGAR PRÓXIMOS 5 DIAS DISPONÍVEIS
    ============================================================ */
    function carregarProximosDias() {
        var unidadeID =
          $("#cadCategoriaId").val() ||
          $("#unit-select").val() ||
          "";

        if (!unidadeID) {
          alert("Selecione uma unidade.");
          return;
        }

        const $container = $("#agendaDias");
        function renderAgendaMensagem(texto, mostrarBotao) {
          let html =
            '<div class="agenda-status-box">' +
              "<p>" + texto + "</p>";
          if (mostrarBotao) {
            html += '<button type="button" class="btn secondary retry-agenda">Tentar novamente</button>';
          }
          html += "</div>";
          $container.html(html);
        }

        $container.html(
          '<div class="loading-box loading-box--dates">' +
            '<div class="spinner" aria-hidden="true"></div>' +
            "<p>Carregando datas disponiveis...</p>" +
          "</div>"
        );
        $("#submitAgendamento").prop("disabled", true);

        $.ajax({
          url: PositivoCRM.ajax_url,
          type: "POST",
          timeout: 15000,
          data: {
            action: "positivo_crm_get_next_available_dates",
            nonce: PositivoCRM.nonce,
            unit: unidadeID
          }
        })
        .done(function (resp) {
          // 🔥 CORREÇÃO PRINCIPAL AQUI
          if (
              !resp.success ||
              !resp.data ||
              !Array.isArray(resp.data.dates) ||
              resp.data.dates.length === 0
          ) {
              renderAgendaMensagem("Nenhuma data disponivel no momento para esta unidade.", true);
              return;
          }
          let html = "";
          resp.data.dates.forEach(function (dia) {
              const dataISO = dia.date;
              const times = Array.isArray(dia.times) ? dia.times : [];
              if (times.length === 0) return;
              const d = new Date(dataISO + "T00:00:00");
              const labelData = d
              .toLocaleDateString("pt-BR", {
                  weekday: "long",
                  day: "2-digit",
                  month: "2-digit"
              })
              .toUpperCase();
              html += `
                <div class="agenda-dia">
                  <div class="agenda-dia-label">${labelData}</div>
                  <div class="horarios-grid">
              `;
              times.forEach(function (hora) {
                  html += `
                    <button
                      type="button"
                      class="time-slot"
                      data-date="${dataISO}"
                      data-time="${hora}"
                    >
                      ${hora}
                    </button>
                  `;
              });
              html += `
                  </div>
                </div>
              `;
          });
          if (!html) {
              renderAgendaMensagem("Nao encontramos horarios disponiveis para as proximas datas desta unidade.", true);
              return;
          }
          $container.html(html);
      })
        .fail(function (_xhr, textStatus) {
          const mensagem = textStatus === "timeout"
            ? "A busca pelas datas demorou mais do que o esperado. Tente novamente."
            : "Erro ao carregar agenda. Tente novamente.";
          renderAgendaMensagem(mensagem, true);
        })
        .always(function () {
          $("#submitAgendamento").prop("disabled", false);
        });
    }
    /* ============================================================
      SELEÇÃO DE HORÁRIO
    ============================================================ */
    $(document).on("click", ".time-slot", function () {
        const $btn = $(this);
        $(".time-slot").removeClass("selected");
        $btn.addClass("selected");
        $("#selected_date").val($btn.data("date"));
        $("#selected_time").val($btn.data("time"));
    });
    $(document).on("click", ".retry-agenda", function () {
      carregarProximosDias();
    });
    document.addEventListener('click', function (e) {
      const slot = e.target.closest('.time-slot');
      if (!slot) return;
      document.querySelectorAll('.time-slot')
          .forEach(el => el.classList.remove('active'));
      slot.classList.add('active');
    });
    // ====================
    // RESTANTE DO JS: SUBMIT AGENDAMENTO, LOAD SERIES, LOAD TIMES...
    // ====================
    function loadUnits() {
      $.ajax({
        url: PositivoCRM.ajax_url,
        type: "POST",
        data: {
          action: "positivo_crm_get_units",
          nonce: PositivoCRM.nonce,
        },
        success: function(response) {
          let unidades = [];
          if (response.success && response.data) {
            if (Array.isArray(response.data)) {
              unidades = response.data;
            } else if (Array.isArray(response.data.result)) {
              unidades = response.data.result;
            } else if (Array.isArray(response.data.value)) {
              unidades = response.data.value;
            }
          }
          if (unidades && unidades.length > 0) {
            for (const key in unitsByCity) { delete unitsByCity[key]; }
            unidades.forEach(function(unit) {
              const nome = unit.cad_name || unit.msdyn_name || unit.name;
              const endereco = unit.pos_endereco_unidade || unit.endereco || "";
              const id = unit.cad_categoriaid || unit.msdyn_organizationalunitid || unit.id;
              if (!nome || !id) return;
              const cidade = extractCity(endereco);
              const cityKey = cidade || "Outra";
              let series = [];
              let servico = null;
              let recurso = null;
              if (unit.pos_mapeamentoseries) {
                try {
                  const parsed = JSON.parse(unit.pos_mapeamentoseries);
                  const unidade = parsed[nome] || parsed["Unidade"]; // fallback
                  if (!unidade) {
                    console.warn("Unidade não encontrada no JSON:", nome);
                    return;
                  }
                  // Corrige inconsistência de chave
                  const seriesObj = unidade.Series || unidade["Séries"];
                  const ag = unidade.Agendamento || {};
                  // Corrige inconsistência de chave
                  servico = ag.Servico || ag["Serviço"] || null;
                  recurso = ag.Recurso || null;
                  if (seriesObj && typeof seriesObj === 'object') {
                    series = Object.entries(seriesObj)
                      .map(([nome, id]) => ({ id, nome }))
                      .filter(s => s.id && s.nome);
                  }
                } catch (e) {
                  console.error("Erro ao parsear séries da unidade:", nome, e);
                }
              } else {
                console.warn("Unidade sem mapeamento de séries:", nome);
              }
              if (!unitsByCity[cityKey]) { unitsByCity[cityKey] = []; }
              unitsByCity[cityKey].push({ id: id, name: nome, endereco: endereco, cidade: cidade, series: series, servico: servico, recurso: recurso});
            });
            window.unidades = unitsByCity;
            const sortedCities = Object.keys(unitsByCity).sort(function(a, b) {
              return a.localeCompare(b, "pt-BR");
            });
            let cityOptions = "<option value=\"\">Selecione a cidade</option>";
            sortedCities.forEach(function(city) {
              cityOptions += `<option value="${city}">${city}</option>`;
            });
            $citySelect.html(cityOptions).prop("disabled", false);
            $unitSelect.html("<option value=\"\">Selecione a unidade</option>").prop("disabled", true);
            $cadCategoria.val("");
          } else {
            console.error("Nenhuma unidade encontrada ou formato inesperado:", response);
            $citySelect.html("<option value=\"\">Erro ao carregar cidades</option>").prop("disabled", true);
            $unitSelect.html("<option value=\"\">Erro ao carregar unidades</option>").prop("disabled", true);
          }
        },
        error: function() {
          console.error("Erro de comunicação ao carregar unidades.");
          $citySelect.html("<option value=\"\">Erro de rede</option>").prop("disabled", true);
          $unitSelect.html("<option value=\"\">Erro de rede</option>").prop("disabled", true);
        }
      });
    }
    // Carrega as séries escolares da API
    // window.loadSeries = () => {
    //   const selectedUnit = $unitSelect.val();
    //   if (!selectedUnit) {
    //     // alert("Por favor, selecione uma unidade antes de escolher a data.");
    //     return;
    //   }
    //   $.ajax({
    //     url: PositivoCRM.ajax_url,
    //     type: "POST",
    //     data: {
    //       action: "positivo_crm_get_series",
    //       nonce: PositivoCRM.nonce,
    //       unit: selectedUnit
    //     },
    //     success: function(response) {
    //       let series = [];
    //       if (response.success && response.data) {
    //         if (Array.isArray(response.data)) {
    //           series = response.data;
    //         } else if (response.data.resultset && response.data.resultset.result) {
    //           series = response.data.resultset.result;
    //         } else if (Array.isArray(response.data.result)) {
    //           series = response.data.result;
    //         } else if (Array.isArray(response.data.value)) {
    //           series = response.data.value;
    //         }
    //       }
    //       if (series && series.length > 0) {
    //         let serieOptions = '<option value=\"\">Selecione a série</option>';
    //         series.forEach(function(s) {
    //           const id = s.cad_servicoeducacionalid || s.id || '';
    //           const name = s.cad_name || s.name || '';
    //           // Remove chaves do GUID se houver
    //           const cleanId = id.replace(/[{}]/g, '');
    //           if (cleanId && name) {
    //             serieOptions += `<option value="${cleanId}" data-name="${name}">${name}</option>`;
    //           }
    //         });
    //         $('.serie-select').html(serieOptions).prop('disabled', false);
    //       } else {
    //         console.error('Nenhuma série encontrada ou formato inesperado:', response);
    //         $('.serie-select').html('<option value=\"\">Erro ao carregar séries</option>').prop('disabled', true);
    //       }
    //     },
    //     error: function() {
    //       console.error('Erro de comunicação ao carregar séries.');
    //       $('.serie-select').html('<option value=\"\">Erro de rede</option>').prop('disabled', true);
    //     }
    //   });
    // }
    $form.on("change", "#agendamentoData", function() {
      const selectedDate = $(this).val();
      const selectedUnit = $unitSelect.val();
      if (!selectedUnit) {
        alert("Por favor, selecione uma unidade antes de escolher a data.");
        return;
      }
      if (!selectedDate) { return; }
      const $grid = $("#horariosGrid");
      $grid.html("<p>Carregando horários...</p>").removeClass("hidden");
      $("#selected_time").val("");
      $.ajax({
        url: PositivoCRM.ajax_url,
        type: "POST",
        data: {
          action: "positivo_crm_get_times",
          nonce: PositivoCRM.nonce,
          date: selectedDate,
          unit: selectedUnit
        },
        success: function(resp) {
          if (resp.success && resp.data && resp.data.times && resp.data.times.length > 0) {
            let html = "<div class=\"times-grid\">";
            resp.data.times.forEach(function(time) {
              html += `<button type="button" class="time-slot" data-time="${time}">${time}</button>`;
            });
            html += "</div>";
            $grid.html(html);
            $grid.off("click.timeSlot").on("click.timeSlot", ".time-slot", function() {
              const timeVal = $(this).data("time");
              $("#selected_time").val(timeVal);
              $grid.find(".time-slot").removeClass("selected");
              $(this).addClass("selected");
            });
          } else {
            $grid.html("<p>Nenhum horário disponível.</p>");
          }
        },
        error: function() {
          $grid.html("<p>Erro ao carregar horários.</p>");
        }
      });
    });
    /* ============================================================
      FUNÇÕES QUE ESTAVAM FALTANDO NO CÓDIGO NOVO
      ============================================================ */
    /* ---------------------- VALIDAR ETAPA ---------------------- */
    function validateStep(step) {
      let isValid = true;
      $form.find(`.step-view[data-step="${step}"]`).find("[required]").each(function () {
        if (!$(this).val()) {
          isValid = false;
          $(this).addClass("error-field");
        } else {
          $(this).removeClass("error-field");
        }
      });
      return isValid;
    }
    /* ---------------------- ADD / REMOVE ALUNO ---------------------- */
    $form.on("click", ".add-aluno", function (e) {
      e.preventDefault();

      const $btn = $(this);
      const $container = $btn.siblings(".aluno-fields").first();

      // 🔥 clone limpo
      const $clone = $container.clone(false, false);
      // console.log($clone);

      // 🔥 REMOVE Select2 antigo do clone
      $clone.find('.select2').remove();
      $clone.find('.escola-select')
        .removeClass('select2-hidden-accessible')
        .removeAttr('data-select2-id')
        .val('');

      // 🔥 LIMPA CAMPOS
      $clone.find("input").val("");
      $clone.find("select").val("").trigger("change");

      // 🔥 REMOVE IDs DUPLICADOS
      $clone.find("[id]").removeAttr("id");

      // 🔥 ADICIONA BOTÃO REMOVER (apenas nos clones)
      const $removeBtn = $(`
        <div style="width:100%;text-align: end;margin-top: 10px;">
          <button type="button" class="btn-remove-aluno" style="margin-bottom: -20px;">
            X
          </button>
        </div>
      `);

      $removeBtn.on("click", function () {
        $clone.remove();
      });

      // Coloca o botão no final do bloco
      $clone.prepend($removeBtn);
      const $hr = $(`
        <hr />
      `);
      // Coloca o botão no final do bloco
      $clone.prepend($hr);
      // 🔥 INSERE NO DOM
      $clone.insertBefore($btn);

      // 🔥 REINICIALIZA SELECT2 APENAS NO CLONE
      initEscolaSelect($clone);

      // 🔥 GARANTE ano de matrícula
      preencherAnoMatricula($clone.find(".ano-matricula-select"));
    });


    /* ---------------------- EDITAR DADOS ---------------------- */
    $form.on("click", ".edit-dados", function (e) {
      e.preventDefault();

      $("#studentsBox").addClass("hidden");
      $(".step-3-manual").removeClass("hidden");
    });
    /* ---------------------- JÁ É ALUNO? ---------------------- */
    $("input[name='ja_aluno']").on("change", function () {
      if ($(this).val() === "sim") {
        $(".alunos-lista").removeClass("hidden");
      } else {
        $(".alunos-lista").addClass("hidden");
      }
    });
    /* ---------------------- ATUALIZAR SÉRIE ---------------------- 
    $form.on("change", ".serie-select", function () {
      const $select = $(this);
      const selected = $select.find("option:selected");
      const serieName = selected.data("name") || selected.text();
      $select.closest(".form-group, div").find(".serie-name").val(serieName);
      if ($select.attr("id") === "responsavel_serie_id") {
        $("#responsavel_serie").val(serieName);
      }
    });*/
    /* ---------------------- SUBMIT DO AGENDAMENTO ---------------------- */
    $form.on("submit", function (e) {
        e.preventDefault();
        $("#agendamento-loading-overlay").removeClass("hidden").fadeIn(150);
        $("#submitAgendamento").prop("disabled", true).text("Enviando...");
        // ✅ GUID da unidade – prioridade: hidden, depois #unit-select
        var unidadeID =
            $("#cadCategoriaId").val() ||
            $("#unit-select").val() ||
            "";
        if (!unidadeID) {
            alert("Selecione uma unidade.");
            return;
        }
        // Serializa o formulário
        let data = $form.serialize();
        // Se já existir crm_unidadeinteresse no serialize, substitui pelo GUID correto
        if (data.includes("crm_unidadeinteresse=")) {
            data = data.replace(
                /crm_unidadeinteresse=[^&]*/g,
                "crm_unidadeinteresse=" + encodeURIComponent(unidadeID)
            );
        } else {
            // Senão, adiciona o campo no final
            data += (data ? "&" : "") + "crm_unidadeinteresse=" + encodeURIComponent(unidadeID);
        }
        $.post(PositivoCRM.ajax_url, {
            action: "positivo_crm_submit_agendamento_public",
            nonce: PositivoCRM.nonce,
            form_data: data,
            // 🔥 Extra: manda também fora do form_data, se o PHP estiver lendo direto de $_POST
            crm_unidadeinteresse: unidadeID
        })
        .done(function (resp) {
            $("#agendamento-loading-overlay").fadeOut(200);
            if (!resp.success) {
                alert(resp.data.message || "Erro no agendamento.");
                return;
            }
            mostrarModalSucesso(resp.data.agendamento);
        })
        .fail(function () {
            $("#agendamento-loading-overlay").fadeOut(200);
            alert("Erro ao comunicar com o servidor.");
        })
        .always(function () {
            $("#submitAgendamento").prop("disabled", false).text("Realizar agendamento");
            $("#agendamento-loading-overlay").fadeOut(200);
        });
    });


  /**
   * Mostrar Modal de Sucesso
   */
  function mostrarModalSucesso(agendamento) {
    const modal = document.getElementById("agendamentoSucessoModal");
    if (!modal) {
        console.error("Modal de sucesso NÃO encontrado!");
        return;
    }

    // Preenche os campos
    modal.querySelector(".resp-nome").textContent   = agendamento.responsavel_nome || "";
    modal.querySelector(".resp-tel").textContent    = agendamento.responsavel_telefone || "";
    modal.querySelector(".resp-email").textContent  = agendamento.responsavel_email || "";

    modal.querySelector(".aluno-nome").textContent  = agendamento.aluno_nome || "";
    modal.querySelector(".aluno-serie").textContent = agendamento.aluno_serie_interesse || "";
    modal.querySelector(".aluno-ano").textContent   = agendamento.aluno_ano_interesse || "";
    modal.querySelector(".aluno-escola").textContent = agendamento.aluno_escola_origem || "";

    modal.querySelector(".visita-unidade").textContent = agendamento.unidade_nome || "";
    modal.querySelector(".visita-data").textContent    = formatarDataBr(agendamento.data_agendamento);
    modal.querySelector(".visita-hora").textContent    = agendamento.hora_agendamento || "";

    // Exibe modal
    modal.style.display = "flex";
  }
  function formatarDataBr(dataIso) {
    if (!dataIso) return "";
    // Entrada: "2025-11-21"
    const partes = dataIso.split("-");
    if (partes.length !== 3) return dataIso;
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
  }


  // Modal Close
  document.querySelector("#agendamentoSucessoModal .btn-fechar")
  .addEventListener("click", function() {
      document.getElementById("agendamentoSucessoModal").style.display = "none";
  });
});
})(jQuery);
JAVASCRIPT;
echo '</script>';
// Agora o JS principal em um <script> separado
echo '<script type="text/javascript">';
echo <<<'JAVASCRIPT'
/*
 * Script de sincronia entre filtros JetEngine e formulário de agendamento.
 *
 * Este arquivo consolida e melhora as funções originais, removendo duplicações
 * e adicionando uma normalização robusta de texto para permitir comparações
 * insensíveis a acentos, travessões e prefixos (ex.: "Colégio Positivo").
 * Ele também aguarda a carga assíncrona das opções de unidade antes de
 * tentar sincronizar o select oculto do formulário.
 */

(function ($) {
  
  // Estado global
  window.syncEscola = {
    cidade: null,
    unidade: null
  };

  // ===============================
  // 1️⃣ Captura seleção do Jet
  // ===============================
  document.addEventListener('change', function (e) {

    if (!e.target.closest('.seleciona-escola select')) return;

    const select = e.target;
    const option = select.options[select.selectedIndex];

    if (select.name === 'cidade') {
      window.syncEscola.cidade = option.text;
      // console.log('🏙️ Cidade capturada:', option.text);
      tentarPreencherFormulario();
    }

    if (select.name === 'colegio') {
      window.syncEscola.unidade = option.text;
      // console.log('🏫 Unidade capturada:', option.text);
      tentarPreencherFormulario();
    }
    atualizarDisabledFormulario();
  });

  // ===============================
  // 2️⃣ Função que espera o formulário
  // ===============================
  function esperarElemento(selector, callback, timeout = 10000) {
    const inicio = Date.now();

    const timer = setInterval(() => {
      const el = document.querySelector(selector);

      if (el) {
        clearInterval(timer);
        callback(el);
      }

      if (Date.now() - inicio > timeout) {
        clearInterval(timer);
        console.warn('⏱️ Timeout aguardando:', selector);
      }
    }, 300);
  }

  // ===============================
  // 3️⃣ Preencher formulário
  // ===============================
  function tentarPreencherFormulario() {
    if (!window.syncEscola.cidade) return;
    // Aguarda select de cidade
    esperarElemento('#city-select', function (citySelect) {
      selecionarOpcao(citySelect, window.syncEscola.cidade);
      // Aguarda select de unidade (normalmente vem depois)
      esperarElemento('#unit-select', function (unitSelect) {
        unitSelect.disabled = false;
        setTimeout(()=>{
          if (window.syncEscola.unidade) {
            // window.loadSeries();
          }
        },2000);
        if (window.syncEscola.unidade) {
          selecionarOpcao(unitSelect, window.syncEscola.unidade);
          atualizarDisabledFormulario();
        }
      });

    });
  }

  // ===============================
  // 4️⃣ Função utilitária
  // ===============================
  function selecionarOpcao(select, texto) {

    if (!select || !texto) return;

    const alvo = texto.trim().toLowerCase();

    const option = Array.from(select.options).find(opt =>
      opt.text.toLowerCase().includes(alvo)
    );

    if (!option) {
      console.warn('❌ Opção não encontrada (contém):', texto);
      return;
    }

    // Seleciona valor
    select.value = option.value;

    // 🔥 Simula interação humana
    select.focus();
    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
    select.dispatchEvent(new Event('blur', { bubbles: true }));

    // console.log('✅ Opção marcada (contains):', option.text);
  }

    // ===============================
    // 5️⃣ Controla disabled do HTML
    // ===============================
    function atualizarDisabledFormulario() {
      // document.querySelector('.top-selects')?.style.setProperty('display','block');
      // return null; 




      const wrapper = document.querySelector('.page-wrapper');
      if (!wrapper) return;

      const liberar = window.syncEscola.cidade && window.syncEscola.unidade;

      const campos = wrapper.querySelectorAll(
        'input, select, textarea, button'
      );

      campos.forEach(el => {

        // 🔓 Nunca desabilita os selects principais
        if (el.id === 'city-select' || el.id === 'unit-select') return;

        el.disabled = !liberar;
      });
      atualizarClasseVisual();

      // console.log(
      //   liberar
      //     ? '🟢 HTML habilitado'
      //     : '🔒 HTML desabilitado'
      // );

    }
    document.addEventListener('DOMContentLoaded', () => {
      atualizarDisabledFormulario();
    });

    function atualizarClasseVisual() {
      const wrapper = document.querySelector('.page-wrapper');
      if (!wrapper) return;

      const liberar = window.syncEscola.cidade && window.syncEscola.unidade;
      wrapper.classList.toggle('is-disabled', !liberar);
    }


})(jQuery);
JAVASCRIPT;
echo '</script>';
