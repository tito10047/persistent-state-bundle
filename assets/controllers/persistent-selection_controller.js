import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

	static targets = ["checkbox", "selectAll"]

	static values = {
		urlToggle:       String,
		urlSelectAll:    String,
		urlSelectRange:  String,
		key:             String,
		manager:         String,
		selectAllClass:  String,
		unselectAllClass: String
	}

	toggle({params: {id}, target}) {
		let checked = target.checked;
		fetch(this._getUrl(this.urlToggleValue, id, checked)).then((response) => {
			if (!response.ok) {
				target.checked = !checked;
				console.error("can't select item #" + id);
			}
		}).catch((error) => {
			target.checked = !checked;
			console.error(error);
		})
	}

	selectAll({target, params: {checked}}) {
		const isCheckbox = target.matches('[type="checkbox"]');
		if (isCheckbox) {
			checked = target.checked;
		}else{
			if (this.hasSelectAllClassValue) {
				checked = !target.classList.contains(this.selectAllClassValue)
			}
		}
		fetch(this._getUrl(this.urlSelectAllValue, null, checked)).then((response) => {
			if (!response.ok) {
				if (isCheckbox) {
					target.checked = !checked;
				}
				console.error("can't select all items")
			} else {
				if (this.hasSelectAllClassValue) {
					if (checked) {
						target.classList.add(this.selectAllClassValue);
					} else {
						target.classList.remove(this.selectAllClassValue);
					}
				}
				if (this.hasUnselectAllClassValue) {
					if (checked) {
						target.classList.remove(this.unselectAllClassValue);
					} else {
						target.classList.add(this.unselectAllClassValue);
					}
				}
				this.checkboxTargets.forEach((checkbox) => {
					checkbox.checked = checked;
				})
			}
		}).catch((error) => {
			if (isCheckbox) {
				target.value = !checked;
			}
			console.error(error);
		})
	}

	selectCurrentPage({target, params: {checked}}) {
		const isCheckbox = target.matches('[type="checkbox"]');
		if (isCheckbox) {
			checked = target.checked;
		}else{
			if (this.hasSelectAllClassValue) {
				checked = !target.classList.contains(this.selectAllClassValue)
			}
		}
		let ids = [];
		this.checkboxTargets.forEach((checkbox) => {
			ids.push(checkbox.value);
		})
		fetch(this._getUrl(this.urlSelectRangeValue, null, checked), {
			method:  'POST',
			headers: {
				'Accept':       'application/json',
				'Content-Type': 'application/json'
			},
			body:    JSON.stringify(ids)
		}).then((response) => {
			if (!response.ok) {
				console.error("can't select all items")
			} else {
				if (this.hasSelectAllClassValue) {
					if (checked) {
						target.classList.add(this.selectAllClassValue);
					} else {
						target.classList.remove(this.selectAllClassValue);
					}
				}
				if (this.hasUnselectAllClassValue) {
					if (checked) {
						target.classList.remove(this.unselectAllClassValue);
					} else {
						target.classList.add(this.unselectAllClassValue);
					}
				}

				this.checkboxTargets.forEach((checkbox) => {
					checkbox.checked = checked;
				})
			}
		}).catch((error) => {
			console.error(error);
		})
	}

	_getUrl(url, id, selected) {
		let params = {
			key:      this.keyValue,
			manager:  this.managerValue,
			selected: selected ? "1" : "0",
		};
		if (id) {
			params["id"] = id;
		}
		return url + '?' + new URLSearchParams(params).toString()
	}
}
