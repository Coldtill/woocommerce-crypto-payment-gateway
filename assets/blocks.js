(function () {
  const { registerPaymentMethod } = window.wc.wcBlocksRegistry
  const { getSetting } = window.wc.wcSettings
  const { createElement } = window.wp.element
  const { decodeEntities } = window.wp.htmlEntities

  const data = getSetting('coldtill_data', {})
  const title = decodeEntities(data.title || 'Pay with crypto')
  const Content = () => createElement('div', null, decodeEntities(data.description || ''))

  registerPaymentMethod({
    name: 'coldtill',
    label: createElement('span', null, title),
    ariaLabel: title,
    content: createElement(Content),
    edit: createElement(Content),
    canMakePayment: () => true,
    supports: { features: ['products'] },
  })
})()
