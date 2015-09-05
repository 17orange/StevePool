// this helper just organizes the given arrays without having to worry about any visuals
function SmartSortHelper(data, start, end)
{
  // if it's 10 or less, just do the insertion sort
  if( (end - start) <= 10 )
  {
    InsertionSort(data, start, end);
  }
  // otherwise merge sort
  else
  {
    MergeSort(data, start, end);
  }
}

// standard merge sort for this section of the table
function MergeSort(data, start, end)
{
  // if it's one element, we're good, just return
  if( start == end )
  {
    return;
  }

  // see what the midpoint is, and sort the individual halves
  var middleIndex = Math.floor((start + end)/2);
  SmartSortHelper(data, start, middleIndex);
  SmartSortHelper(data, middleIndex, end);

  // now merge them back together
  var halfA = data.slice(start, middleIndex);
  var halfB = data.slice(middleIndex, end);
  var indexA = 0, indexB = 0, indexData = start;
  while( indexData < end )
  {
    if( indexB >= halfB.length || ((indexA < halfA.length) && SwapRows(halfA[indexA], halfB[indexB])) )
    {
      data[indexData] = halfA[indexA];
      indexA++;
    }
    else
    {
      data[indexData] = halfB[indexB];
      indexB++;
    }
    indexData += 1;
  }
}

// standard insertion sort for this section of the table
function InsertionSort(data, start, end)
{
  // insertion sort
  for( var j=start + 1; j<end; j+=1 )
  {
    for( var k=start; k<j; k++ )
    {
      if( SwapRows(data[j], data[k]) )
      {
        var dataObj = data.splice(j,1)[0];
        data.splice(k, 0, dataObj);
        k = j;
      }
    }
  }
}

// compare whether these smart rows are supposed to switch places
function SwapRows(row1, row2)
{
  // run through the tiebreakers
  var cap = row1.length;
  var swapEm = 0;
  for( var m=1; m<cap && swapEm == 0; m += 2 )
  {
    if( (row1[m+1] && (row2[m] < row1[m])) || (!row1[m+1] && (row2[m] > row1[m])) )
    {
      swapEm = 1;
    }
    else if( (row1[m+1] && (row2[m] > row1[m])) || (!row1[m+1] && (row2[m] < row1[m])) )
    {
      swapEm = -1;
    }
  }

  return (swapEm == 1);
}
