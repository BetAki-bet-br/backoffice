import BasePage from './BasePage';

export default class LobbiesPage extends BasePage {
  constructor() {
    super('/lobbies');
  }

  selectVertical(vertical) {
    cy.contains('button, a, label', new RegExp(vertical, 'i')).click();
    return this;
  }

  getSections() {
    return cy.get('.section-item, .lobby-section, [data-section]');
  }

  clickAddSection() {
    cy.contains('button', /Adicionar|Nova Seção|Add/i).click();
    return this;
  }

  clickSave() {
    cy.contains('button', /Salvar|Save/i).click();
    return this;
  }

  // --- Section Modal ---

  getModal() {
    return cy.get('.modal.show');
  }

  selectSectionType(type) {
    cy.get('#f_type, #f_section_type, select[name*="type"]').first().select(type);
    return this;
  }

  fillSectionTitle(title) {
    cy.get('#f_title, input[name*="title"]').first().clear().type(title);
    return this;
  }

  fillDisplayCount(count) {
    cy.get('#f_display_count, input[name*="display_count"], input[name*="count"]').first().clear().type(String(count));
    return this;
  }

  clickSaveSection() {
    this.getModal().find('.btn-primary').click();
    return this;
  }
}
