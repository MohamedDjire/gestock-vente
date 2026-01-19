<template>
  <div class="paiement-forfait-page">
    <router-link to="/gestion-compte" class="back-link">← Retour</router-link>

    <div v-if="!forfaitValid" class="error-card">
      <p>Forfait introuvable. Choisissez un forfait depuis Gestion du compte.</p>
      <router-link to="/gestion-compte" class="btn-back">Retour</router-link>
    </div>

    <div v-else class="payment-card-wrap">
      <!-- Colonne gauche : Détails du paiement -->
      <div class="payment-details">
        <h2 class="payment-title">Détails du paiement</h2>

        <p class="section-label">Moyen de paiement</p>
        <div class="payment-methods-row">
          <button
            v-for="op in paymentOperators"
            :key="op.id"
            type="button"
            class="method-card"
            :class="{ selected: selectedMethod === op.id }"
            @click="selectedMethod = op.id"
          >
            <span class="method-radio" :class="{ checked: selectedMethod === op.id }">
              <span v-if="selectedMethod === op.id" class="radio-dot"></span>
            </span>
            <span class="method-logo">
              <img :src="op.logo || ('/payments/' + op.id + '.svg')" :alt="op.name" @error="onLogoError($event, op)" />
              <span v-if="failedLogos[op.id]" class="logo-fb">{{ logoFallback(op) }}</span>
            </span>
            <span class="method-name">{{ op.name }}</span>
          </button>
        </div>

        <!-- Formulaire carte (VISA / Mastercard) -->
        <transition name="card-form">
          <div v-if="showCardForm" class="card-form-block">
            <div class="field-group">
              <label for="card-email">Email *</label>
              <input
                id="card-email"
                v-model="cardEmail"
                type="email"
                class="form-input"
                placeholder="exemple@email.com"
                autocomplete="email"
              />
            </div>
            <div class="field-group">
              <label for="card-number">Numéro de carte *</label>
              <input
                id="card-number"
                v-model="cardNumber"
                type="text"
                class="form-input"
                placeholder="xxxx xxxx xxxx xxxx"
                maxlength="19"
                autocomplete="cc-number"
                @input="formatCardNumber"
              />
            </div>
            <div class="field-row">
              <div class="field-group">
                <label for="card-expiry">Date d'expiration *</label>
                <input
                  id="card-expiry"
                  v-model="cardExpiry"
                  type="text"
                  class="form-input"
                  placeholder="MM/AA"
                  maxlength="5"
                  autocomplete="cc-exp"
                  @input="formatExpiry"
                />
              </div>
              <div class="field-group">
                <label for="card-cvv">CVV *</label>
                <input
                  id="card-cvv"
                  v-model="cardCvv"
                  type="text"
                  class="form-input"
                  placeholder="***"
                  maxlength="4"
                  inputmode="numeric"
                  autocomplete="cc-csc"
                  @input="formatCvv"
                />
              </div>
            </div>
          </div>
        </transition>

        <p v-if="submitError" class="submit-error">{{ submitError }}</p>

        <!-- Récapitulatif -->
        <div class="order-summary">
          <div class="summary-row">
            <span>Sous-total</span>
            <span>{{ formatPrice(forfait.prix) }}</span>
          </div>
          <div class="summary-row total-row">
            <span>Total</span>
            <span>{{ formatPrice(forfait.prix) }}</span>
          </div>
        </div>

        <button type="button" class="btn-pay" :disabled="validating" @click="validerPaiement">
          {{ validating ? 'Redirection...' : 'Payer' }}
        </button>
      </div>

      <!-- Colonne droite : Récap forfait -->
      <div class="plan-summary">
        <div class="plan-block">
          <h3 class="plan-name">{{ forfait.nom_forfait }}</h3>
          <div class="plan-price">{{ formatPrice(forfait.prix) }} <span class="plan-duree">/ {{ forfait.duree_jours != null ? forfait.duree_jours + ' jours' : '—' }}</span></div>
          <ul class="plan-features">
            <li>Forfait actif dès validation</li>
            <li>Accès selon votre forfait</li>
          </ul>
        </div>
      </div>
    </div>

    <transition name="toast">
      <div v-if="toastMessage" :class="['toast', toastType]">{{ toastMessage }}</div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCurrency } from '../composables/useCurrency.js'

const route = useRoute()
const { formatPrice } = useCurrency()

const selectedMethod = ref(null)
const validating = ref(false)
const submitError = ref('')
const toastMessage = ref('')
const toastType = ref('info')

const CARD_METHODS = ['visa', 'mastercard']

const paymentOperators = [
  { id: 'wave', name: 'Wave', logo: 'https://paywall.senaffiche.com/assets/images/logo_wave_circle_818.png' },
  { id: 'orange-money', name: 'Orange Money', logo: 'https://logos-world.net/wp-content/uploads/2023/02/Orange-Money-Logo.png' },
  { id: 'mtn-momo', name: 'MTN MoMo', logo: 'https://mgmt.manhom.com/images/2952/%D8%B4%D8%B1%D9%83%D8%A9-mtn-%D8%B3%D9%88%D8%B1%D9%8A%D8%A9.webp' },
  { id: 'moov-money', name: 'Moov Money', logo: 'https://mir-s3-cdn-cf.behance.net/projects/404/b01cb838681877.Y3JvcCw1NjgsNDQ0LDAsNjE.png' },
  { id: 'visa', name: 'VISA', logo: 'https://logos-world.net/wp-content/uploads/2020/04/Visa-Logo.png' },
  { id: 'mastercard', name: 'Mastercard', logo: 'https://logos-world.net/wp-content/uploads/2020/09/MasterCard-Logo-1979-1990.png' }
]
const failedLogos = ref({})

const cardEmail = ref('')
const cardNumber = ref('')
const cardExpiry = ref('')
const cardCvv = ref('')

const showCardForm = computed(() => CARD_METHODS.includes(selectedMethod.value))

function formatCardNumber() {
  let v = cardNumber.value.replace(/\D/g, '').slice(0, 16)
  cardNumber.value = v.replace(/(.{4})/g, '$1 ').trim()
}
function formatExpiry() {
  let v = cardExpiry.value.replace(/\D/g, '').slice(0, 4)
  cardExpiry.value = v.length >= 2 ? v.slice(0, 2) + '/' + v.slice(2) : v
}
function formatCvv() {
  cardCvv.value = cardCvv.value.replace(/\D/g, '').slice(0, 4)
}
function onLogoError(e, op) {
  e.target.style.display = 'none'
  failedLogos.value = { ...failedLogos.value, [op.id]: true }
}
function logoFallback(op) {
  return op.name.replace(/\s+/g, '').replace(/-/g, '').slice(0, 2).toUpperCase()
}

const forfait = computed(() => ({
  id_forfait: route.query.id_forfait ? Number(route.query.id_forfait) : null,
  nom_forfait: route.query.nom_forfait || '',
  prix: route.query.prix != null ? Number(route.query.prix) : null,
  duree_jours: route.query.duree_jours != null ? Number(route.query.duree_jours) : null
}))

const forfaitValid = computed(() => {
  const f = forfait.value
  return f.id_forfait > 0 && f.nom_forfait && f.prix != null && f.prix >= 0
})

function showToast(msg, type = 'info') {
  toastMessage.value = msg
  toastType.value = type
  setTimeout(() => { toastMessage.value = '' }, 3500)
}

function validateCardForm() {
  const em = (cardEmail.value || '').trim()
  if (!em) return 'Veuillez renseigner votre email.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) return 'Email invalide.'
  const num = (cardNumber.value || '').replace(/\D/g, '')
  if (num.length < 13 || num.length > 19) return 'Numéro de carte invalide.'
  const exp = (cardExpiry.value || '').trim()
  const mm = parseInt(exp.slice(0, 2), 10)
  const aa = parseInt(exp.slice(3, 5), 10)
  if (!/^\d{2}\/\d{2}$/.test(exp) || mm < 1 || mm > 12) return 'Date d\'expiration invalide (MM/AA).'
  const y = 2000 + aa
  const now = new Date()
  if (y < now.getFullYear() || (y === now.getFullYear() && mm < now.getMonth() + 1)) return 'Carte expirée.'
  const cvv = (cardCvv.value || '').replace(/\D/g, '')
  if (cvv.length < 3 || cvv.length > 4) return 'CVV invalide (3 ou 4 chiffres).'
  return null
}

async function validerPaiement() {
  submitError.value = ''
  if (!selectedMethod.value) {
    submitError.value = 'Veuillez choisir un moyen de paiement.'
    return
  }
  if (showCardForm.value) {
    const err = validateCardForm()
    if (err) {
      submitError.value = err
      return
    }
  }
  validating.value = true
  try {
    // TODO: appeler l'API selon selectedMethod (données carte : cardEmail, cardNumber, cardExpiry, cardCvv)
    showToast('Intégration des APIs de paiement en cours.', 'info')
  } catch (e) {
    submitError.value = 'Une erreur est survenue.'
    showToast('Erreur.', 'error')
  } finally {
    validating.value = false
  }
}

onMounted(() => {
  selectedMethod.value = null
  submitError.value = ''
})
</script>

<style scoped>
.paiement-forfait-page {
  min-height: 100%;
  padding: 1.5rem 1.25rem;
  background: #f3f4f6;
}

.back-link {
  display: inline-block;
  color: #1a5f4a;
  text-decoration: none;
  font-size: 0.9rem;
  margin-bottom: 1.25rem;
}
.back-link:hover { text-decoration: underline; }

.error-card {
  background: #fff;
  border: 1px solid #fecaca;
  border-radius: 12px;
  padding: 1.5rem;
  text-align: center;
  max-width: 420px;
  margin: 0 auto;
}
.error-card p { color: #6b7280; margin-bottom: 1rem; font-size: 0.95rem; }
.btn-back {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: #1a5f4a;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 600;
}
.btn-back:hover { background: #145a47; color: #fff; }

/* Carte principale 2 colonnes */
.payment-card-wrap {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 1.5rem;
  max-width: 900px;
  margin: 0 auto;
  align-items: start;
}

.payment-details {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.payment-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 1.25rem;
}

.section-label {
  font-size: 0.9rem;
  color: #6b7280;
  margin-bottom: 0.75rem;
  font-weight: 500;
}

/* Ligne de moyens de paiement (style radio) */
.payment-methods-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.method-card {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 1rem;
  background: #fff;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.method-card:hover { border-color: #1a5f4a; }
.method-card.selected {
  border-color: #10b981;
  box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
}

.method-radio {
  width: 18px;
  height: 18px;
  border: 2px solid #d1d5db;
  border-radius: 50%;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
.method-card.selected .method-radio {
  border-color: #10b981;
  background: #10b981;
}
.radio-dot {
  width: 6px;
  height: 6px;
  background: #fff;
  border-radius: 50%;
}

.method-logo {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.method-logo img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.logo-fb {
  font-size: 0.7rem;
  font-weight: 700;
  color: #6b7280;
  background: #f3f4f6;
  padding: 0.25rem 0.4rem;
  border-radius: 6px;
}

.method-name {
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
}

/* Formulaire carte */
.card-form-block {
  margin-bottom: 1.5rem;
  padding-top: 0.25rem;
}
.card-form-block .field-group {
  margin-bottom: 1rem;
}
.card-form-block label {
  display: block;
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.35rem;
}
.card-form-block .form-input {
  width: 100%;
  padding: 0.6rem 0.75rem;
  font-size: 0.95rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  outline: none;
  transition: border-color 0.2s;
}
.card-form-block .form-input:focus {
  border-color: #2563eb;
}
.card-form-block .form-input::placeholder {
  color: #9ca3af;
}
.card-form-block .field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.card-form-enter-active,
.card-form-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.card-form-enter-from,
.card-form-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Récapitulatif */
.order-summary {
  border-top: 1px solid #e5e7eb;
  padding-top: 1rem;
  margin-bottom: 1.25rem;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.95rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}
.summary-row.total-row {
  font-size: 1.05rem;
  font-weight: 700;
  color: #111827;
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
}

.submit-error {
  color: #dc2626;
  font-size: 0.85rem;
  margin: -0.5rem 0 0.75rem;
}

.btn-pay {
  width: 100%;
  padding: 0.95rem 1.25rem;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-pay:hover:not(:disabled) { background: #1d4ed8; }
.btn-pay:disabled { opacity: 0.6; cursor: not-allowed; }

/* Colonne droite : résumé forfait */
.plan-summary {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  position: sticky;
  top: 1rem;
}

.plan-block { }
.plan-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 0.5rem;
}
.plan-price {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a5f4a;
  margin-bottom: 1rem;
}
.plan-duree { font-size: 0.9rem; font-weight: 500; color: #6b7280; }

.plan-features {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.9rem;
  color: #6b7280;
  line-height: 1.6;
}
.plan-features li::before {
  content: '✓ ';
  color: #10b981;
  font-weight: 700;
  margin-right: 0.35rem;
}

.toast {
  position: fixed;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 500;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  z-index: 9999;
}
.toast.info { background: #1a5f4a; color: #fff; }
.toast.error { background: #dc2626; color: #fff; }
.toast-enter-active, .toast-leave-active { transition: opacity 0.25s, transform 0.25s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(8px); }

@media (max-width: 768px) {
  .payment-card-wrap { grid-template-columns: 1fr; }
  .plan-summary { position: static; }
  .payment-methods-row { justify-content: flex-start; }
}
@media (max-width: 480px) {
  .payment-methods-row { flex-direction: column; }
  .method-card { width: 100%; }
}
</style>
