/** Camp.site drag rule chain — work / travel / off subset. Keep in lockstep with ScheduleDragRules.php. */

export const MODE_CELLS = 'cells';
export const MODE_SELECTION = 'selection';
export const MODE_REVERT = 'revert';

export function rangeDates(a, b) {
    const start = a < b ? a : b;
    const end = a < b ? b : a;
    const dates = [];
    const cursor = new Date(`${start}T00:00:00`);
    const last = new Date(`${end}T00:00:00`);

    while (cursor <= last) {
        dates.push(toDateString(cursor));
        cursor.setDate(cursor.getDate() + 1);
    }

    return dates;
}

export function typeAt(types, date) {
    return types[date] || 'off';
}

export function shiftDate(date, days) {
    const cursor = new Date(`${date}T00:00:00`);
    cursor.setDate(cursor.getDate() + days);

    return toDateString(cursor);
}

export function resolveDrag(sourceDate, dropDate, types, backDragRevert = false) {
    if (sourceDate === dropDate) {
        return { mode: MODE_SELECTION, cells: {} };
    }

    const range = rangeDates(sourceDate, dropDate);
    const sourceType = typeAt(types, sourceDate);
    const dropType = typeAt(types, dropDate);
    const sourceIsTravel = sourceType === 'travel';
    const forward = sourceDate < dropDate;
    const between = range.slice(1, -1);
    const yellowsInRange = countType(types, range, 'travel');
    const leftOfSource = shiftDate(sourceDate, -1);
    const rightOfSource = shiftDate(sourceDate, 1);
    const leftIsWork = typeAt(types, leftOfSource) === 'work';
    const rightIsWork = typeAt(types, rightOfSource) === 'work';
    const leftIsTravel = typeAt(types, leftOfSource) === 'travel';
    const rightIsTravel = typeAt(types, rightOfSource) === 'travel';
    const nonSource = range.filter((date) => date !== sourceDate);
    const nonSourceAllOff = allType(types, nonSource, 'off');
    const betweenAllWork = between.length > 0 && allType(types, between, 'work');
    const betweenAllOff = between.length > 0 && allType(types, between, 'off');
    const betweenHasOff = hasType(types, between, 'off');
    const dropIsTravel = dropType === 'travel';
    const dropIsWork = dropType === 'work';

    if (sourceIsTravel && dropIsTravel && betweenAllWork) {
        return fill(range, 'off');
    }

    if (backDragRevert) {
        return { mode: MODE_REVERT, cells: slice(types, range) };
    }

    const attachToBlueEnd =
        sourceIsTravel && yellowsInRange === 1 && ((forward && leftIsWork) || (!forward && rightIsWork));
    const dragToBlueOffDays = sourceIsTravel && !attachToBlueEnd && yellowsInRange === 1 && dropIsWork;
    const loneToLone =
        sourceIsTravel &&
        dropIsTravel &&
        betweenAllOff &&
        isLoneTravel(types, sourceDate) &&
        isLoneTravel(types, dropDate);
    const combineShifts = sourceIsTravel && forward && dropIsTravel && betweenHasOff;
    const dropOnYellowAllWork = sourceIsTravel && dropIsTravel && !attachToBlueEnd && !dragToBlueOffDays;
    const departureShortenLeft =
        sourceIsTravel &&
        !forward &&
        !attachToBlueEnd &&
        !dragToBlueOffDays &&
        !combineShifts &&
        !dropOnYellowAllWork &&
        leftIsWork &&
        yellowsInRange === 1;
    const adjacentTravelMove =
        sourceIsTravel &&
        !attachToBlueEnd &&
        !dragToBlueOffDays &&
        !combineShifts &&
        !dropOnYellowAllWork &&
        !departureShortenLeft &&
        (leftIsTravel || rightIsTravel);
    const whiteSelect = sourceType === 'off' && nonSourceAllOff;
    const workPaint = sourceType === 'work';

    if (attachToBlueEnd) {
        return dropAndRest(range, dropDate, 'travel', 'work');
    }

    if (dragToBlueOffDays) {
        return dropAndRest(range, dropDate, 'travel', 'off');
    }

    if (loneToLone) {
        return bookend(range);
    }

    if (combineShifts) {
        return dropAndRest(range, dropDate, 'travel', 'work');
    }

    if (dropOnYellowAllWork) {
        return fill(range, 'work');
    }

    if (departureShortenLeft) {
        return dropAndRest(range, dropDate, 'travel', 'off');
    }

    if (adjacentTravelMove) {
        return dropAndRest(range, dropDate, 'travel', 'work');
    }

    if (sourceIsTravel) {
        return bookend(range);
    }

    if (whiteSelect) {
        return { mode: MODE_SELECTION, cells: {} };
    }

    if (workPaint) {
        return fill(range, 'work');
    }

    return { mode: MODE_SELECTION, cells: {} };
}

export function bookend(range) {
    const cells = {};
    const last = range.length - 1;

    range.forEach((date, index) => {
        cells[date] = index === 0 || index === last ? 'travel' : 'work';
    });

    return { mode: MODE_CELLS, cells };
}

export function paint(dates, type) {
    return fill(dates, type);
}

function fill(dates, type) {
    const cells = {};
    dates.forEach((date) => {
        cells[date] = type;
    });

    return { mode: MODE_CELLS, cells };
}

function dropAndRest(range, dropDate, dropType, restType) {
    const cells = {};
    range.forEach((date) => {
        cells[date] = date === dropDate ? dropType : restType;
    });

    return { mode: MODE_CELLS, cells };
}

function slice(types, dates) {
    const cells = {};
    dates.forEach((date) => {
        cells[date] = typeAt(types, date);
    });

    return cells;
}

function countType(types, dates, type) {
    return dates.filter((date) => typeAt(types, date) === type).length;
}

function allType(types, dates, type) {
    return dates.every((date) => typeAt(types, date) === type);
}

function hasType(types, dates, type) {
    return dates.some((date) => typeAt(types, date) === type);
}

function isLoneTravel(types, date) {
    return typeAt(types, shiftDate(date, -1)) === 'off' && typeAt(types, shiftDate(date, 1)) === 'off';
}

function toDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}
