class SearchableSelect {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.select = wrapper.querySelector(".searchable-select");
        this.input = wrapper.querySelector(".searchable-input");
        this.dropdown = wrapper.querySelector(".searchable-dropdown");
        this.isMultiple = this.select.hasAttribute("multiple");
        this.tagsContainer = this.isMultiple ? wrapper.querySelector(".searchable-tags") : null;
        this.selectedValues = new Set();
        this.activeIndex = -1; // track highlighted item

        this.input.addEventListener("focus", () => this.toggle(true));
        this.input.addEventListener("input", () => this.filter());
        this.input.addEventListener("keydown", (e) => this.handleKey(e));

        document.addEventListener("click", (e) => {
            if (!this.wrapper.contains(e.target)) this.toggle(false);
        });

        // Set initial value
        this.setInitialValue();
        
        this.populate();
    }

    // NEW METHOD: Set initial value from the select element
    setInitialValue() {
        if (!this.isMultiple) {
            const selectedOption = this.select.options[this.select.selectedIndex];
            if (selectedOption && selectedOption.value) {
                this.input.value = selectedOption.textContent;
            }
        } else {
            // For multiple select, show selected values as tags
            Array.from(this.select.selectedOptions).forEach(option => {
                if (option.value) {
                    this.selectedValues.add(option.value);
                    this.addTag(option.value, option.textContent);
                }
            });
            this.updateSelect();
        }
    }

    populate() {
        this.dropdown.innerHTML = "";
        Array.from(this.select.options).forEach(opt => {
            if (opt.value && (!this.isMultiple || !this.selectedValues.has(opt.value))) {
                const li = document.createElement("li");
                li.textContent = opt.text;
                li.dataset.value = opt.value;
                li.className = "cursor-pointer px-3 py-2 hover:bg-indigo-100 dark:hover:bg-gray-600";
                li.onclick = () => this.selectOption(opt.value, opt.text);
                this.dropdown.appendChild(li);
            }
        });
        this.activeIndex = -1; // reset highlight
    }

    filter() {
        const text = this.input.value.toLowerCase();
        Array.from(this.dropdown.children).forEach(li => {
            li.style.display = li.textContent.toLowerCase().includes(text) ? "block" : "none";
        });
        this.activeIndex = -1; // reset on filter
    }

    handleKey(e) {
        const items = Array.from(this.dropdown.querySelectorAll("li"))
            .filter(li => li.style.display !== "none");
        if (!items.length) return;

        switch (e.key) {
            case "ArrowDown":
                e.preventDefault();
                this.activeIndex = (this.activeIndex + 1) % items.length;
                this.highlight(items);
                break;
            case "ArrowUp":
                e.preventDefault();
                this.activeIndex = (this.activeIndex - 1 + items.length) % items.length;
                this.highlight(items);
                break;
            case "Enter":
                e.preventDefault();
                if (this.activeIndex >= 0) {
                    const li = items[this.activeIndex];
                    this.selectOption(li.dataset.value, li.textContent);
                }
                break;
            case "Escape":
                this.toggle(false);
                break;
        }
    }

    highlight(items) {
        items.forEach((li, i) => {
            li.classList.toggle("bg-indigo-200", i === this.activeIndex);
        });
    }

    selectOption(value, text) {
        if (this.isMultiple) {
            if (this.selectedValues.has(value)) return;
            this.selectedValues.add(value);
            this.addTag(value, text);
            this.updateSelect();
            this.input.value = "";
            this.populate();
        } else {
            this.input.value = text;
            this.select.value = value;
            this.select.dispatchEvent(new Event("change"));
            this.toggle(false);
        }
    }

    addTag(value, text) {
        const tag = document.createElement("span");
        tag.className = "bg-indigo-100 dark:bg-gray-600 text-indigo-800 dark:text-white text-xs px-2 py-1 rounded flex items-center gap-1";
        tag.textContent = text;

        const removeBtn = document.createElement("button");
        removeBtn.textContent = "×";
        removeBtn.className = "ml-1 text-red-500 hover:text-red-700";
        removeBtn.onclick = () => {
            this.selectedValues.delete(value);
            tag.remove();
            this.updateSelect();
            this.populate();
        };

        tag.appendChild(removeBtn);
        this.tagsContainer.insertBefore(tag, this.input);
    }

    updateSelect() {
        Array.from(this.select.options).forEach(opt => {
            opt.selected = this.selectedValues.has(opt.value);
        });
        this.select.dispatchEvent(new Event("change"));
    }

    toggle(show) {
        if (show) {
            this.populate();
            this.dropdown.classList.remove("hidden");
        } else {
            this.dropdown.classList.add("hidden");
        }
    }

    setValues(values) {
        if (this.isMultiple) {
            values.forEach(val => {
                const opt = Array.from(this.select.options).find(o => o.value == val);
                if (opt) this.selectOption(opt.value, opt.text);
            });
        } else {
            const opt = Array.from(this.select.options).find(o => o.value == values);
            if (opt) this.selectOption(opt.value, opt.text);
        }
    }

    // NEW METHOD: Clear the current selection
    clear() {
        if (!this.isMultiple) {
            this.input.value = "";
            this.select.value = "";
        } else {
            this.selectedValues.clear();
            this.tagsContainer.innerHTML = '';
            this.input.value = "";
            this.updateSelect();
        }
        this.populate();
    }
}

document.querySelectorAll(".searchable-wrapper").forEach(wrapper => {
    new SearchableSelect(wrapper);
});