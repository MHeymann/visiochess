import matplotlib.pyplot as plt
from scipy.interpolate import interp1d
import statsmodels.api as sm

# introduce some floats in our x-values
x = list(range(8, 25) + range(8, 25) + range(8, 25))
y = list([228.322, #80
        222.912, #90
        217.482, #100
        211.704, #110
        210.781, #120
        207.843, #130
        207.545, #140
        206.064, #150
        206.658, #160
        205.859, #170
        206.652, #180
        207.345, #190
        207.552, #200
        208.452, #210
        209.454, #220
        210.577, #230
        210.406 #240
        ] + [228.878, #80
        223.003, #90
        217.677, #100
        211.781, #110
        210.811, #120
        208.247, #130
        207.666, #140
        206.490, #150
        207.013, #160
        205.860, #170
        206.952, #180
        208.474, #190
        207.712, #200
        208.929, #210
        209.979, #220
        210.894, #230
        211.958 #240
        ] + [229.188, #80
        223.854, #90
        217.780, #100
        212.665, #110
        211.262, #120
        208.741, #130
        207.700, #140
        206.805, #150
        207.416, #160
        206.324, #170
        207.427, #180
        208.544, #190
        207.763, #200
        209.221, #210
        210.700, #220
        211.414, #230
        212.874 #240
        ]
        )

# lowess will return our "smoothed" data with a y value for at every x-value
lowess = sm.nonparametric.lowess(y, x, frac=.3)

# unpack the lowess smoothed points to their values
lowess_x = list(zip(*lowess))[0]
lowess_y = list(zip(*lowess))[1]

# run scipy's interpolation. There is also extrapolation I believe
f = interp1d(lowess_x, lowess_y, bounds_error=False)

xnew = [i/10. for i in range(400)]

# this this generate y values for our xvalues by our interpolator
# it will MISS values outsite of the x window (less than 3, greater than 33)
# There might be a better approach, but you can run a for loop
#and if the value is out of the range, use f(min(lowess_x)) or f(max(lowess_x))
ynew = f(xnew)


plt.ylabel("Time taken in seconds")
plt.xlabel("Batch size in tens of queries")
plt.title("Time taken to populate database over batch size of each query:")
plt.plot(x, y, 'o')
plt.plot(lowess_x, lowess_y, '*')
plt.plot(xnew, ynew, '-')
plt.show()
