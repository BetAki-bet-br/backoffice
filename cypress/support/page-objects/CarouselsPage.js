import BasePage from './BasePage';

export default class CarouselsPage extends BasePage {
  constructor() {
    super('/carousels');
  }

  // --- Filters ---

  getSearchInput() {
    return cy.get('#q');
  }

  searchByQuery(query) {
    this.getSearchInput().clear().type(query);
    cy.get('#btnSearch').click();
    return this;
  }

  clearFilters() {
    cy.get('#btnClear').click();
    return this;
  }

  // --- CRUD ---

  clickNew() {
    cy.get('#btnNew').click();
    return this;
  }

  clickReload() {
    cy.get('#btnReload').click();
    return this;
  }

  // --- Modal Form ---

  getModal() {
    return cy.get('#editModal');
  }

  getModalTitle() {
    return cy.get('#editTitle');
  }

  fillName(name) {
    cy.get('#f_name').clear().type(name);
    return this;
  }

  fillSlug(slug) {
    cy.get('#f_slug').clear().type(slug);
    return this;
  }

  clickAddSlide() {
    cy.get('#addSlide').click();
    return this;
  }

  getSlides() {
    return cy.get('.slide-form');
  }

  removeSlide(index) {
    this.getSlides().eq(index).find('[data-action="remove-slide"]').click();
    return this;
  }

  fillSlideField(slideIndex, field, value) {
    this.getSlides().eq(slideIndex).find(`[data-field="${field}"]`).clear().type(value);
    return this;
  }

  clickSave() {
    cy.get('#btnSave').click();
    return this;
  }

  getSaveError() {
    return cy.get('#saveError');
  }

  // --- Row Actions ---

  clickEditOnRow(id) {
    cy.get(`button[data-action="edit"][data-id="${id}"]`).click();
    return this;
  }

  clickDeleteOnRow(id) {
    cy.get(`button[data-action="delete"][data-id="${id}"]`).click();
    return this;
  }

  // --- Pagination ---

  clickNext() {
    cy.get('#nextBtn').click();
    return this;
  }

  clickPrev() {
    cy.get('#prevBtn').click();
    return this;
  }

  getPaginationInfo() {
    return cy.get('#paginationInfo');
  }
}
